var ReminderModificationMessage = require('../queues/remmodmsg');
var modexceptions = require('./modexceptions');
var Serialize = require('../utils/serialize');
var AWSDynamoTable = require('../aws/awsdynamodb');
var mysql = require('../utils/wmysqlw');
var JobStream = require('../utils/jobstream');
var Concurrent = require('../utils/concurrent');
var util = require('./jobutil');
var env = require('../environemnt');

var RPrefModificationJob = function (modmsg) {
	this.modmsg = modmsg;
	this.validateRPref(modmsg.object);
	this.rpref = modmsg.object;
	// dynamodb tables
	this.Timeline = AWSDynamoTable.get('TimelineTable');
	this.Prepared = AWSDynamoTable.get('PreparedTable');
	this.Preferences = AWSDynamoTable.get('PreferencesTable');
}

RPrefModificationJob.CreateWithUpdatedActionFromRPref =
function (id, rangeId, last_modified, version, item) {
	return new RPrefModificationJob({
		action:ReminderModificationMessage.UpdatedAction,
		object:util.rprefFromReminderPreferenceItem(id, rangeId, last_modified, version, item)
	});
}

RPrefModificationJob.CreateWithUpdatedActionFromRow = function (row) {
	return new RPrefModificationJob({
		action:ReminderModificationMessage.UpdatedAction,
		object:row
	});
}

RPrefModificationJob.prototype.validateRPref = function (rpref) {
	var mustExist = [
		'reminder_pref_id',
		'calendar_id',
		'event_id',
		'task_id',
		'event_reminder_pref',
		'user_id',
		'mins_before',
		'absolute_date',
		'relative',
		'aggregate',
		'last_modified',
		'version'
	];
	for (var i in mustExist) {
		if (rpref[mustExist[i]] === undefined)
			throw {
				code:modexceptions.ModificationJobDescriptionMalformation,
				name:modexceptions.ModificationJobDescriptionMalformation,
				message:'Reminder Perference is malformed.',
				statusCode:400,
				retryable:false
			};
	}
	if (
		rpref.task_id !== null && rpref.task_id != false &&
		rpref.event_id !== null && rpref.event_id != false
	)
		throw {
			code:modexceptions.ModificationJobDescriptionMalformation,
			name:modexceptions.ModificationJobDescriptionMalformation,
			message:'Reminder Perference is malformed.',
			statusCode:400,
			retryable:false
		};
}

RPrefModificationJob.prototype.run = function(callback) {
	switch (this.modmsg.action) {
		case ReminderModificationMessage.CreatedAction:
			this.runCreated(callback);
			break;
		case ReminderModificationMessage.UpdatedAction:
			this.runUpdated(callback);
			break;
		case ReminderModificationMessage.DeletedAction:
			this.runDeleted(callback);
			break;
		default:
			callback({
				code:'UnrecognizedJobAction',
				name:'UnrecognizedJobAction',
				message:'Expected Created, Updated, or Deleted action',
				statusCode:400,
				retryable:false
			});
			break;
	}
	return this;
};

// job plugs
RPrefModificationJob.prototype.runCreated = function(callback) {
	this._runCreated(callback);
}

RPrefModificationJob.prototype.runUpdated = function(callback) {
	this._runCreated(callback);
}

RPrefModificationJob.prototype.runDeleted = function(callback) {
	//console.log('>>>>>>>>Reminder Preference Delete Job');
	this._runDeleted(function (err) {
		//console.log('<<<<<<<<Reminder Preference Delete Job');
		callback(err);
	});
}

/**
*	Reminder Preference created job
*/
RPrefModificationJob.prototype._runCreated = function (callback) {
	var _this = this;
	//console.log('>>>>>>>>Reminder Preference Create/Update Job');
	(new Concurrent(function (err) {
		//console.log('<<<<<<<<Reminder Preference Create/Update Job');
		callback(err);
	})).
		add({target:this, fnc:this._createPreferencesItem}).
	execute(function () {
		_this._createPreparedAndTimelineItems(function (err) {
			//console.log('>>>>>>>>Reminder Create/Update Preference Job');
			callback(err);
		});
	});
}


RPrefModificationJob.prototype._createPreferencesItem = function (continue_callback) {
	item = {};
	if (this.rpref.aggregate == true)
		item.id = { S:this.rpref.calendar_id} ;
	else if (this.rpref.event_reminder_pref == true)
		item.id = { S:this.rpref.event_id };
	else
		item.id = { S:this.rpref.task_id };
	item.rangeId = { S:this.rpref.reminder_pref_id };
	item = util.reminderPreferenceItemWithRPref(item, this.rpref);
	item = util.vectorClockItem(item,[
		{
			last_modified:this.rpref.last_modified,
			version:this.rpref.version
		}
	]);
	item = util.garbageCollectionItem(item);

	function _shouldPut_ (item, resource) {
		// collision handler
		var ivc = util.vectorClockFromItem(item);
		var rvc = util.vectorClockFromItem(resource);
		var isGC = util.garbageCollectionItemIsMarkedDeleted(resource);
		var shouldPut = false;
		if (!isGC && util.cmpVectorClockPrime(ivc, rvc, 0) > 0) {
			shouldPut = { Item:item, Expected: {} };
			shouldPut.Expected[util.vectorClockAttrName()] = {
				Value:resource[util.vectorClockAttrName()]
			};
		}
		return shouldPut;
	};
	var attrs = [util.vectorClockAttrName()];
	attrs = attrs.concat(util.garbageCollectionAttrNames());
	this.Preferences.syncCondPutItem(item, 'id', 'rangeId', attrs, _shouldPut_, function (err, data) {
		continue_callback(err === null, err);
	});
}

RPrefModificationJob.prototype._createPreparedAndTimelineItems = function (callback) {
	var tw = util.preparedTimeWindowTS();
	var relativeSec = util.reminderPreferenceRelativeTSWithPref(this.rpref);
	if (this.rpref.relative == false && !util.timeInTimeWindowTS(tw, this.rpref.absolute_date)) {
		callback(null);
		return;
	}

	var query = '';
	if (this.rpref.event_reminder_pref == true) {
		query = "\
			SELECT\
				event_id,\
				UNIX_TIMESTAMP(event_start) as event_start,\
				UNIX_TIMESTAMP(last_modified) as last_modified,\
				version\
			FROM events\
			WHERE\
		";
		if (this.rpref.aggregate == true)
			query += " calendar_id = '" + this.rpref.calendar_id + "'";
		else
			query += " event_id = '" + this.rpref.event_id + "'";
		if (this.rpref.relative == true) {
			query += " AND UNIX_TIMESTAMP(event_start) + "+relativeSec+" >= "+tw.infimum;
			query += " AND UNIX_TIMESTAMP(event_start) + "+relativeSec+" <= "+tw.supremum;
		}
	} else {
		query = "\
			SELECT\
				task_id,\
				UNIX_TIMESTAMP(due_time) as due_time,\
				UNIX_TIMESTAMP(last_modified) as last_modified,\
				version\
			FROM tasks\
			WHERE\
		";
		if (this.rpref.aggregate == true)
			query += " calendar_id = '" + this.rpref.calendar_id + "'";
		else
			query += " task_id = '" + this.rpref.task_id + "'";
		if (this.rpref.relative == true) {
			query += " AND UNIX_TIMESTAMP(due_time) + "+relativeSec+" >= "+tw.infimum;
			query += " AND UNIX_TIMESTAMP(due_time) + "+relativeSec+" <= "+tw.supremum;
		}
	}
	query += " AND active = 1";

	var _this = this;
	mysql.getWriteConnection(function (err, connection) {
		if (err) {
			callback({
				code:err.code,
				name:'MySQL Error',
				message:'Error was encounter while attempting to connect to MySQL database',
				statusCode:400,
				retryable:true
			});
		} else {
			var jobstream = new JobStream(env.get('JobInternalBatchingSize'),
				function (rows, continue_callback) {
					// Stream
					var _prepared = {};
					var _old = {};
					connection.pause();
					(new Serialize).
						then(
							{target:_this,params:rows,fnc:_this._createPreparedItemsFromRows},
							function (err, prepared, old) {
								var shouldContinue = true;
								if (err) {
									shouldContinue = false;
									continue_callback(shouldContinue, err);
								} else {
									_prepared = prepared;
									_old = old;
								}
								return shouldContinue;
							}
						).
						then(
							function (callback) {
								_this.garbageCollectTimelineItemsFromOldItems(_old, callback);
							},
							function (err) {
								var shouldContinue = true;
								if (err) {
									shouldContinue = false;
									continue_callback(shouldContinue, err);
								}
								return shouldContinue;
							}
						).
						then(
							function (callback) {
								_this.createTimelineItemsFromPreparedItems(_prepared, callback);
							},
							function (err) {
								var shouldContinue = true;
								if (err) {
									shouldContinue = false;
									continue_callback(shouldContinue, err);
								}
								return shouldContinue;
							}
						).
						then(function () {
							connection.resume();
							continue_callback(true, null)
						}).
					execute();
			} , function (err) {
				// Abort
				connection.destroy();
				callback(err);
			});

			(connection.query(query)).
				on('error', function (err) {
					if (jobstream)
						jobstream.abort({
							code:err.code,
							name:'MySQL Error',
							message:'Error was encounter while attempting to query the MySQL database',
							statusCode:400,
							retryable:true
						});
				}).
				on('result', function (row) {if (jobstream) jobstream.stream(row);}).
				on('end', function () {
					connection.destroy();
					if (jobstream) jobstream.end(callback, null);
				})
			;
		}
	});
}

RPrefModificationJob.prototype._createPreparedItemsFromRows = function (rows, callback) {
	var params = [];
	for (var i in rows) {
		var rows_i = rows[i];
		for (var j in rows_i) {
			var row = rows_i[j];
			var item = {};
			if (row.task_id === undefined) {
				item.id = row.event_id;
				item.assetTimeTS = row.event_start;
			}
			else {
				item.id = row.task_id;
				item.assetTimeTS = row.due_time;
			}
			item.last_modified = row.last_modified;
			item.version = row.version;
			params.push(item);
		}
	}
	return this.createPreparedItemsFromParams(params, callback);
}

RPrefModificationJob.prototype.createPreparedItemsFromParams = function (params, callback) {
	var _this = this;
	var syncAttrs = ['timeTS', 'assetTimeTS', util.vectorClockAttrName()];
	syncAttrs = syncAttrs.concat(util.garbageCollectionAttrNames());
	var prepared = {};
	var old = {};
	var concurrent = new Concurrent(function (err) {
		callback(err, null);
		prepared = null;
		concurrent = null;
	});

	for (var i in params) {
		concurrent.add({target:{p:params[i]}, fnc:function (continue_callback) {
			var item = {id: { S:this.p.id }};
			var assetTimeTS = this.p.assetTimeTS;
			item.rangeId = {S:_this.rpref.reminder_pref_id};
			item.timeTS = {N:util.notificationTime(assetTimeTS, _this.rpref) + ''};
			item.assetTimeTS = {N:assetTimeTS + ''};
			item = util.reminderPreferenceItemWithRPref(item, _this.rpref);
			item = util.vectorClockItem(item,[
				{
					last_modified:this.p.last_modified,
					version:this.p.version
				},
				{
					last_modified:_this.rpref.last_modified,
					version:_this.rpref.version
				}
			]);
			item = util.garbageCollectionItem(item);

			prepared[item.id.S] = item;
			function _shouldPut_ (item, resource) {
				// collision handler
				var ivc = util.vectorClockFromItem(item);
				var rvc = util.vectorClockFromItem(resource);
				var isGC = util.garbageCollectionItemIsMarkedDeleted(resource);
				var shouldPut = false;
				if (!isGC && util.cmpVectorClockPrime(ivc, rvc, 1) > 0 ||
						(
							util.cmpVectorClockPrime(ivc, rvc, 1) == 0 &&
							util.cmpVectorClock(ivc, rvc, 0) > 0
						)
				) {
					if (util.cmpVectorClock(ivc, rvc, 0) < 0) {
						item.assetTimeTS = resource.assetTimeTS;
						item.timeTS = {
							N:util.notificationTime(item.assetTimeTS.N, _this.rpref) + ''
						};
						item = util.vectorClockItem(item, [rvc[0], ivc[1]]);
						prepared[item.id.S] = item;
					}
					shouldPut = { Item:item, Expected: {}, ReturnValues:'ALL_OLD' };
					shouldPut.Expected[util.vectorClockAttrName()] = {
						Value:resource[util.vectorClockAttrName()],
					};
				};
				return shouldPut;
			};
			_this.Prepared.syncCondPutItem(item, 'id', 'rangeId', syncAttrs, _shouldPut_,
				function (err, data) 
				{
					if (
						data && data.Attributes !== undefined &&
						data.Attributes.id !== undefined
					) {
						old[data.Attributes.id.S] = data.Attributes;
					}
					continue_callback(err === null, err);
				}
			);
		}});
	}

	concurrent.execute(function () {
		callback(null, prepared, old);
		prepared = null;
		concurrent = null;
	});
}

RPrefModificationJob.prototype.garbageCollectTimelineItemsFromOldItems = function (old, callback) {
	var _this = this;
	var concurrent = new Concurrent(function (err) {
		callback(err);
		concurrent = null;
	});

	var hasStuffToExecute = false;
	for (var i in old) {
		hasStuffToExecute = true;
		concurrent.add({target:{o:old[i]}, fnc:function (continue_callback) {
			var o = this.o;
			item = {};
			item.id = o.timeTS;
			item.rangeId = { S:o.id.S+':'+o.rangeId.S };
			item.assetId = o.id;
			item.userId = o.userId;
			item[util.vectorClockAttrName()] = o[util.vectorClockAttrName()];
			item = util.garbageCollectionDeletedItem(item);

			function _shouldPut_ (item, resource) {return false;};
			var params = { Item:item, Expected: {} };
			params.Expected[util.vectorClockAttrName()] = {
				Value:item[util.vectorClockAttrName()]
			};
			//console.log("Running garbageCollectTimelineItemsFromOldItems!");
			//console.log(old);
			_this.Timeline.syncPutItem(params, 'id', 'rangeId', [util.vectorClockAttrName()], _shouldPut_,
				function (err, data) {
					continue_callback(err === null, err);
				}
			);
		}});
	}

	if (hasStuffToExecute) {
		concurrent.execute(function () {
			callback(null);
			concurrent = null;
		});
	} else
		callback(null);
}

RPrefModificationJob.prototype.createTimelineItemsFromPreparedItems = function (prepared, callback) {
	// callback(null);
	// return;
	var _this = this;
	var syncAttrs = [util.vectorClockAttrName()];
	var concurrent = new Concurrent(function (err) {
		callback(err);
		concurrent = null;
	});

	var hasStuffToExecute = false;
	for (var i in prepared) {
		hasStuffToExecute = true;
		concurrent.add({target:{p:prepared[i]}, fnc:function (continue_callback) {
			var p = this.p;
			item = {};
			item.id = p.timeTS;
			item.rangeId = { S:p.id.S+':'+p.rangeId.S };
			item.assetId = p.id;
			item.userId = p.userId;
			item[util.vectorClockAttrName()] = p[util.vectorClockAttrName()];
			item = util.garbageCollectionItem(item);

			function _shouldPut_ (item, resource) {
				// collision handler
				var ivc = util.vectorClockFromItem(item);
				var rvc = util.vectorClockFromItem(resource);
				var shouldPut = false;
				if (util.cmpVectorClockPrime(ivc, rvc, 1) > 0 ||
						(
							util.cmpVectorClockPrime(ivc, rvc, 1) == 0 &&
							util.cmpVectorClockPrime(ivc, rvc, 0) > 0
						)
				){
					shouldPut = { Item:item, Expected: {}};
					shouldPut.Expected[util.vectorClockAttrName()] = {
						Value:resource[util.vectorClockAttrName()],
					};
				}
				return shouldPut;
			};

			_this.Timeline.syncCondPutItem(item, 'id', 'rangeId', syncAttrs, _shouldPut_,
				function (err, data) {
					continue_callback(err === null, err);
				}
			);
		}});
	}

	if (hasStuffToExecute) {
		concurrent.execute(function () {
			callback(null);
			concurrent = null;
		});
	} else
		callback(null);
}



/**
*	Reminder Preference deleted job
*/
RPrefModificationJob.prototype._runDeleted = function (callback) {
	var _this = this;
	(new Concurrent(function (err) {
		callback(err);
	})).
		add(function(continue_callback) {
			var id = null
			if (_this.rpref.aggregate == true)
				id = { S:_this.rpref.calendar_id} ;
			else if (_this.rpref.event_reminder_pref == true)
				id = { S:_this.rpref.event_id };
			else
				id = { S:_this.rpref.task_id };
			//console.log(id);
			var rangeId = { S:_this.rpref.reminder_pref_id };
			var gc = util.garbageCollectionDeletedItem({});
			var gccAttrs = util.garbageCollectionAttrNames();
			//console.log(gccAttrs);
			var params = {
				Key: { HashKeyElement:id, RangeKeyElement:rangeId },
				AttributeUpdates: {}
			};
			for (var i in gccAttrs) {
				var attr = gccAttrs[i];
				params.AttributeUpdates[attr] = { Value: gc[attr], Action: 'PUT' };
			}
			//console.log("PARAMS:"+params.Key+":"+params.AttributeUpdates);
			_this.Preferences.updateItem(params).
				on('success', function(response) {
					//console.log("SUCCESS:"+response);
					continue_callback(true, null);
				}).
				on('error', function (err) {
					if (err.code === 'ResourceNotFoundExceptions')
						continue_callback(true, null);
					else
						continue_callback(false, err);
				}).
			send();
		}).
	execute(function () {
		callback(null);
	});
}


module.exports = RPrefModificationJob;
