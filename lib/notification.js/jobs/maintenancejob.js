var mysql = require('../utils/wmysqlw');
var Serialize = require('../utils/serialize');
var JobStream = require('../utils/jobstream');
var Concurrent = require('../utils/concurrent');
var util = require('./jobutil');
var env = require('../environemnt');
var RPrefModificationJob = require('./rprefmodjob');
var AWSDynamoTable = require('../aws/awsdynamodb');
var Concurrent = require('../utils/concurrent');

var MaintenanceJob = function (jobmsg) {
	this.jobmsg = jobmsg;
	// dynamodb tables
	this.Timeline = AWSDynamoTable.get('TimelineTable');
	this.Prepared = AWSDynamoTable.get('PreparedTable');
	this.Preferences = AWSDynamoTable.get('PreferencesTable');
}

MaintenanceJob.prototype.run = function (callback) {
	function _breakIfError_ (err) {
		if (err === undefined || err === null) return true;
		callback(err); return false;
	}
	(new Serialize).
		then({target:this, fnc:this._createPreparedAndTimelineItems}, _breakIfError_).
		then({target:this, fnc:this._garbageCollect}, _breakIfError_).
		then(function () {callback(null);}).
	execute();
}

MaintenanceJob.prototype._createPreparedAndTimelineItems = function (callback) {
	//console.log("createPrepareAndTimelineItems");
	var tw = util.preparedTimeWindowTS();
	
	var query = "\
		SELECT\
			r.reminder_pref_id,\
			r.calendar_id,\
			r.aggregate,\
			r.event_reminder_pref,\
			r.mins_before,\
			UNIX_TIMESTAMP(r.absolute_date) as absolute_date,\
			r.relative,\
			r.user_id,\
			UNIX_TIMESTAMP(r.last_modified) as last_modified,\
			r.version as version,\
			e.event_id,\
			UNIX_TIMESTAMP(e.event_start) as event_start,\
			UNIX_TIMESTAMP(e.last_modified) as e_last_modified,\
			e.version as e_version,\
			null as task_id,\
			null as due_time,\
			null as t_last_modifed,\
			null t_version\
		FROM reminder_prefs r\
		LEFT JOIN calendar_subs s\
			ON r.aggregate = 1\
				AND r.calendar_id = s.calendar_id\
				AND r.user_id = s.user_id\
		LEFT JOIN events e\
			ON (\
					(r.aggregate = 1 AND e.calendar_id = s.calendar_id)\
					OR\
					(r.aggregate = 0 AND e.event_id = r.event_id)\
				)\
		WHERE\
			r.active = 1\
			AND (\
				e.active = 1\
			)\
			AND (\
				(r.relative = 0\
					AND UNIX_TIMESTAMP(r.absolute_date) >= "+ tw.infimum +
					"AND UNIX_TIMESTAMP(r.absolute_date) <= "+ tw.supremum +
				")\
				OR\
				(r.relative = 1\
					AND (\
							UNIX_TIMESTAMP(e.event_start) >= "+ tw.infimum +
							"AND UNIX_TIMESTAMP(e.event_start) <= "+ tw.supremum +
					")\
				)\
			)\
		UNION\
		SELECT\
			r.reminder_pref_id,\
			r.calendar_id,\
			r.aggregate,\
			r.event_reminder_pref,\
			r.mins_before,\
			UNIX_TIMESTAMP(r.absolute_date) as absolute_date,\
			r.relative,\
			r.user_id,\
			UNIX_TIMESTAMP(r.last_modified) as last_modified,\
			r.version as version,\
			null as event_id,\
			null as event_start,\
			null as e_last_modified,\
			null as e_version,\
			t.task_id,\
			UNIX_TIMESTAMP(t.due_time) as due_time,\
			UNIX_TIMESTAMP(t.last_modified) as t_last_modifed,\
			t.version as t_version\
		FROM reminder_prefs r\
		LEFT JOIN calendar_subs s\
			ON r.aggregate = 1\
				AND r.calendar_id = s.calendar_id\
				AND r.user_id = s.user_id\
		LEFT JOIN tasks t\
			ON (\
					(r.aggregate = 1 AND t.calendar_id = s.calendar_id)\
					OR\
					(r.aggregate = 0 AND t.task_id = r.task_id)\
				)\
		WHERE\
			r.active = 1\
			AND (\
				t.active = 1\
			)\
			AND (\
				(r.relative = 0\
					AND UNIX_TIMESTAMP(r.absolute_date) >= "+ tw.infimum +
					"AND UNIX_TIMESTAMP(r.absolute_date) <= "+ tw.supremum +
				")\
				OR\
				(r.relative = 1\
					AND (\
							UNIX_TIMESTAMP(t.due_time) >= "+ tw.infimum +
							"AND UNIX_TIMESTAMP(t.due_time) <= "+ tw.supremum +
					")\
				)\
			)\
		ORDER BY reminder_pref_id\
	";
	
	/*
	var query = "\
		SELECT\
			r.reminder_pref_id,\
			r.calendar_id,\
			r.aggregate,\
			r.event_reminder_pref,\
			r.mins_before,\
			UNIX_TIMESTAMP(r.absolute_date) as absolute_date,\
			r.relative,\
			r.user_id,\
			UNIX_TIMESTAMP(r.last_modified) as last_modified,\
			r.version as version,\
			e.event_id,\
			UNIX_TIMESTAMP(e.event_start) as event_start,\
			UNIX_TIMESTAMP(e.last_modified) as e_last_modified,\
			e.version as e_version,\
			t.task_id,\
			UNIX_TIMESTAMP(t.due_time) as due_time,\
			UNIX_TIMESTAMP(t.last_modified) as t_last_modifed,\
			t.version as t_version\
		FROM reminder_prefs r\
		LEFT JOIN calendar_subs s\
			ON r.aggregate = 1\
				AND r.calendar_id = s.calendar_id\
				AND r.user_id = s.user_id\
		LEFT JOIN events e\
			ON r.event_reminder_pref = 1\
				AND (\
					(r.aggregate = 1 AND e.calendar_id = s.calendar_id)\
					OR\
					(r.aggregate = 0 AND e.event_id = r.event_id)\
				)\
		LEFT JOIN tasks t\
			ON r.event_reminder_pref = 0\
				AND (\
					(r.aggregate = 1 AND t.calendar_id = s.calendar_id)\
					OR\
					(r.aggregate = 0 AND t.task_id = r.task_id)\
				)\
		WHERE\
			r.active = 1\
			AND (\
				(r.event_reminder_pref = 1 AND e.active = 1)\
				OR\
				(r.event_reminder_pref = 0 AND t.active = 1)\
			)\
			AND (\
				(r.relative = 0\
					AND UNIX_TIMESTAMP(r.absolute_date) >="+ tw.infimum +
					"AND UNIX_TIMESTAMP(r.absolute_date) <="+ tw.supremum +
				")\
				OR\
				(r.relative = 1\
					AND (\
						(r.event_reminder_pref = 1\
							AND UNIX_TIMESTAMP(e.event_start) >="+ tw.infimum +
							"AND UNIX_TIMESTAMP(e.event_start) <="+ tw.supremum +
						")\
						OR\
						(r.event_reminder_pref = 0\
							AND UNIX_TIMESTAMP(t.due_time) >="+ tw.infimum +
							"AND UNIX_TIMESTAMP(t.due_time) <="+ tw.supremum +
						")\
					)\
				)\
			)\
		ORDER BY r.reminder_pref_id\
	";
	*/

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
		}
		else {
			var jobstream = new JobStream(env.get('MaintenanceJobInternalBatchSize'),
				function (rows, continue_callback) {
					// Stream
					connection.pause();
					_this._createPreparedAndTimelineItemsFromRows(rows, function (err) {
						connection.resume();
						if (err)
							continue_callback(false, err);
						else
							continue_callback(true);
					});
					
					
				},
				function (err) {
					// Abort
					//connection.destroy();
					callback(err);
					jobstream = null;
				}
			);

			((connection.query(query)).
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
					if (jobstream)
						jobstream.end(function () {
							callback(null);
							jobstream = null;
						});
				})
			);
		}
	});
}

MaintenanceJob.prototype._createPreparedAndTimelineItemsFromRows = function (rows, callback) {
	//console.log("createPreparedAndTimelineItemsFromRows");
	var concurrent = new Concurrent(function (err) {
		callback(err);
		concurrent = null;
	});

	var preferences = {};
	for (var i in rows) {
		var rows_i = rows[i];
		for (var j in rows_i) {
			var row = rows_i[j];
			//console.log(JSON.stringify(row));
			if (preferences[row.reminder_pref_id] === undefined)
				preferences[row.reminder_pref_id] = [];
			preferences[row.reminder_pref_id].push(row);
		}
	}

	var _this = this;
	var hasStuffToExecute = false;
	for (var i in preferences) {
		var assets = preferences[i];
		if (assets.length < 1) continue; // should not happen, but here for safety

		var rprefJob = RPrefModificationJob.CreateWithUpdatedActionFromRow(assets[0]);
		var params = [];
		for (var j in assets) {
			var asset = assets[j];
			//if (asset.event_reminder_pref == true) {
			if (asset.event_id != null) {
				params.push({
					id:asset.event_id,
					assetTimeTS:asset.event_start,
					last_modified:asset.e_last_modified,
					version:asset.e_version
				});
			} else {
				params.push({
					id:asset.task_id,
					assetTimeTS:asset.due_time,
					last_modified:asset.t_last_modifed,
					version:asset.t_version
				});
			}
		}
		if (params.length > 0) {
			hasStuffToExecute = true;
			concurrent.add({
				target:{rprefJob:rprefJob, params:params},
				fnc:function (continue_callback) {
					_this._createPreparedAndTimelineItemsWithParams(this, function (err) {
						if (err)
							continue_callback(false, err);
						else
							continue_callback(true);
					})
				}
			});
		}
	}


	if (hasStuffToExecute) {
		concurrent.execute(function () {
			callback(null);
			concurrent = null;
		});
	}
	else
		callback(null);
}

MaintenanceJob.prototype._createPreparedAndTimelineItemsWithParams = function (params, callback) {
	//console.log("createPreparedAndTimelineItemsWithParams");
	var rprefJob = params.rprefJob;
	var params = params.params;
	// poor man's serial
	rprefJob.createPreparedItemsFromParams(params, function (err, prepared, old) {
		if (err !== null) {
			callback(err);
			return;
		}
		rprefJob.garbageCollectTimelineItemsFromOldItems(old, function(err) {
			if (err !== null) {
				callback(err);
				return;
			}
			rprefJob.createTimelineItemsFromPreparedItems(prepared, callback);
		});
	});
}

MaintenanceJob.prototype._garbageCollect = function (callback) {
	//console.log("garbageCollect");
	var _this = this;
	(new Concurrent (function (err) {
		callback(err);
	})).
		add(function (continue_callback) {
			_this._garbageCollectPreferences(function (err) {
				continue_callback(err === null, err);
			});
		}).
		add(function (continue_callback) {
			_this._garbageCollectPrepared(function (err) {
				continue_callback(err === null, err);
			});
		}).
		add(function (continue_callback) {
			_this._garbageCollectTimeline(function (err) {
				continue_callback(err === null, err);
			});
		}).
	execute(function () {
		callback(null);
	});
}

MaintenanceJob.prototype._garbageCollectTimeline = function (callback) {
	//console.log("garbageCollectTimeline");
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		var itemsToDelete = [];
		items = items.pop();
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i) {
				var item = items_i[j];
				//console.log("DELETING!");
				itemsToDelete.push({
					DeleteRequest: {Key: {
						HashKeyElement:item.id,
						RangeKeyElement:item.rangeId
					}}
				});
			}
		}

		if (itemsToDelete.length > 0) {
			AWSDynamoTable.batchWriteItem([{table:_this.Timeline,requests:itemsToDelete}]).
				on('error', function (err) {
					console.dir(err);
				}).
				on('complete', function (response) {
					continue_callback(true);
				}).
			send();
		} else
			continue_callback(true);
	}, function (err) {
		// Abort
		callback(err);
		jobstream = null;
	});

	
	scanTableForStaleWithStream(
		this.Timeline,
		'id',
		env.get('MaintenanceJobInternalBatchSize'),
		jobstream,
		function () {
			// Complete
			callback(null);
			jobstream = null;
		}
	);
	
}

MaintenanceJob.prototype._garbageCollectPrepared = function (callback) {
	//console.log("garbageCollectPrepared");
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		var itemsToDelete = [];
		items = items.pop();
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i) {
				var item = items_i[j];
				//console.log("DELETING!");
				itemsToDelete.push({
					DeleteRequest: {Key: {
						HashKeyElement:item.id,
						RangeKeyElement:item.rangeId
					}}
				});
			}
		}

		if (itemsToDelete.length > 0) {
			AWSDynamoTable.batchWriteItem([{table:_this.Prepared,requests:itemsToDelete}]).
				on('error', function (err) {
					console.dir(err);
				}).
				on('complete', function (response) {
					continue_callback(true);
				}).
			send();
		} else
			continue_callback(true);
	}, function (err) {
		// Abort
		callback(err);
		jobstream = null;
	});

	
	scanTableForStaleWithStream(
		this.Prepared,
		'timeTS',
		env.get('MaintenanceJobInternalBatchSize'),
		jobstream,
		function () {
			// Complete
			callback(null);
			jobstream = null;
		}
	);
	
}

MaintenanceJob.prototype._garbageCollectPreferences = function (callback) {
	//console.log("garbageCollectPreferences");
	var pastTS = parseInt(Date.now() / 1000) + env.get('DeleteWindowInfimumDt_sec');
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		var itemsToDelete = [];
		items = items.pop();
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i) {
				var item = items_i[j];
				//console.log("here");
				//console.log(item.deletedAtTS.N+":"+pastTS);
				if (item.deletedAtTS !== undefined && item.deletedAtTS.N < pastTS) {
					//console.log("DELETING!");
					itemsToDelete.push({
						DeleteRequest: {Key: {
							HashKeyElement:item.id,
							RangeKeyElement:item.rangeId
						}}
					});
				}
			}
		}
		if (itemsToDelete.length > 0) {
			AWSDynamoTable.batchWriteItem([{table:_this.Preferences,requests:itemsToDelete}]).
				on('error', function (err) {
					console.dir(err);
				}).
				on('complete', function (response) {
					continue_callback(true);
				}).
			send();
		} else
			continue_callback(true);
	}, function (err) {
		// Abort
		callback(err);
		jobstream = null;
	});

	scanTableForDeadWithStream(
		this.Preferences,
		'isDeleted',
		env.get('MaintenanceJobInternalBatchSize'),
		jobstream,
		function () {
			// Complete
			callback(null);
			jobstream = null;
		}
	);
}

function scanTableForDeadWithStream (table, condAttr, limit, jobstream, callback, ExclusiveStartKey) {
	//console.log("scanTableForDeadWithStream");
	var params = {ScanFilter:{}, ConsistentRead:true, ScanIndexForward:true};
	params.ScanFilter[condAttr] = {AttributeValueList:[{N:'1'}], ComparisonOperator:'EQ'};
	if (limit !== -1)
		params.Limit = limit;
	if (ExclusiveStartKey !== undefined)
		params.ExclusiveStartKey = ExclusiveStartKey;
	table.scan(params).
		on('success', function (response) {
			var data = response.data;
			jobstream.stream(data.Items);
			if (data.LastEvaluatedKey === undefined || data.LastEvaluatedKey === null)
				jobstream.end(callback);
			else
				scanTableForDeadWithStream(
					table, condAttr, limit, jobstream, callback,  data.LastEvaluatedKey
				);
		}).
		on('error', function (err) {
			jobstream.abort(err);
		}).
	send();
}

function scanTableForStaleWithStream (table, condAttr, limit, jobstream, callback, ExclusiveStartKey) {
	//console.log("scanTableForStaleWithStream");
	var pastTS = parseInt(Date.now() / 1000) + env.get('DeleteWindowInfimumDt_sec');
	//console.log(pastTS);
	var params = {ScanFilter:{}, ConsistentRead:true, ScanIndexForward:true};
	params.ScanFilter[condAttr] = {AttributeValueList:[{N:pastTS+''}], ComparisonOperator:'LT'};
	if (limit !== -1)
		params.Limit = limit;
	if (ExclusiveStartKey !== undefined)
		params.ExclusiveStartKey = ExclusiveStartKey;
	table.scan(params).
		on('success', function (response) {
			var data = response.data;
			jobstream.stream(data.Items);
			if (data.LastEvaluatedKey === undefined || data.LastEvaluatedKey === null)
				jobstream.end(callback);
			else
				scanTableForStaleWithStream(
					table, condAttr, limit, jobstream, callback,  data.LastEvaluatedKey
				);
		}).
		on('error', function (err) {
			jobstream.abort(err);
		}).
	send();
}

module.exports = MaintenanceJob;
