var ReminderModificationMessage = require('../queues/remmodmsg');
var modexceptions = require('./modexceptions');
var Serialize = require('../utils/serialize');
var AWSDynamoTable = require('../aws/awsdynamodb');
var JobStream = require('../utils/jobstream');
var Concurrent = require('../utils/concurrent');
var util = require('./jobutil');
var RPrefModificationJob = require('./rprefmodjob');
var env = require('../environemnt');
var AssetUpdateReminderJob = require('./assetupdatereminderjob');

var EventModificationJob = function (modmsg) {
	this.modmsg = modmsg;
	this.validateEvent(modmsg.object);
	this.event = modmsg.object;
	this.skipIsEvent = false;
	// dynamodb table
	this.Preferences = AWSDynamoTable.get('PreferencesTable');
}

EventModificationJob.CreateWithCreateAction = function (id, cal_id, start, lmod, version, isCast) {
	var ev = new EventModificationJob({
		action:ReminderModificationMessage.CreatedAction,
		object: {
			event_id:id,
			calendar_id:cal_id,
			event_start:start,
			last_modified:lmod,
			version:version
		}
	});
	if (isCast !== undefined) ev.skipIsEvent = isCast;
	return ev;
}

EventModificationJob.CreateWithUpdatedAction = function (id, cal_id, start, lmod, version, isCast) {
	var ev = new EventModificationJob({
		action:ReminderModificationMessage.UpdatedAction,
		object: {
			event_id:id,
			calendar_id:cal_id,
			event_start:start,
			last_modified:lmod,
			version:version
		}
	});
	if (isCast !== undefined) ev.skipIsEvent = isCast;
	return ev;
}

EventModificationJob.CreateWithDeleteAction = function (id, cal_id, start, lmod, version, isCast) {
	var ev = new EventModificationJob({
		action:ReminderModificationMessage.DeletedAction,
		object: {
			event_id:id,
			calendar_id:cal_id,
			event_start:start,
			last_modified:lmod,
			version:version
		}
	});
	if (isCast !== undefined) ev.skipIsEvent = isCast;
	return ev;
}

EventModificationJob.prototype.validateEvent = function (event) {
	var mustExist = [
		'event_id',
		'calendar_id',
		'event_start',
		'last_modified',
		'version'
	];
	for (var i in mustExist) {
		if (event[mustExist[i]] === undefined)
			throw {
				code:modexceptions.ModificationJobDescriptionMalformation,
				name:modexceptions.ModificationJobDescriptionMalformation,
				message:'Reminder Perference is malformed.',
				statusCode:400,
				retryable:false
			};
	}
}

EventModificationJob.prototype.run = function(callback) {
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
EventModificationJob.prototype.runCreated = function(callback) {
	this._runCreated(callback);
}

EventModificationJob.prototype.runUpdated = function(callback) {
	this._runCreated(callback);
}

EventModificationJob.prototype.runDeleted = function(callback) {
	this._runDeleted(callback);
}


/**
*	Event created job
*/
EventModificationJob.prototype._runCreated = function (callback) {
	function _breakOnError_ (err) {
		if (err === undefined || err === null) return true;
		//console.log('<<<<<<<<Event Create/Update Job');
		callback(err); return false;
	}

	if (this.skipIsEvent)
		var au = new AssetUpdateReminderJob(this.event.event_id,'task');
	else
		var au = new AssetUpdateReminderJob(this.event.event_id,'event');

	//console.log('>>>>>>>>Event Create/Update Job');
	(new Serialize).
		then({
			target:this, 
			params:this.event.event_id, 
			fnc:this._createPreparedAndTimelineItemsWithId
		}, _breakOnError_).
		then({
			target:this,
			params:this.event.calendar_id,
			fnc:this._createPreparedAndTimelineItemsWithId
		}, _breakOnError_).
		then({
			target:au,
			params:null,
			fnc:au.run
		}, _breakOnError_).
		then(function () {
			//console.log('<<<<<<<<Event Create/Update Job xxx');
			callback(null);
		}).
	execute();
}

EventModificationJob.prototype._createPreparedAndTimelineItemsWithId = function (id, callback) {
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		var concurrent = new Concurrent(function (err) {
			continue_callback(false, err);
			concurrent = null;
		});

		var hasStuffToExecute = false;
		var tw = util.preparedTimeWindowTS();
		items = items.pop();
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i) {
				var item = items_i[j];
				if (util.garbageCollectionItemIsMarkedDeleted(item))
					continue;
				if (!_this.skipIsEvent && !util.reminderPreferenceIsEventType(item))
					continue;
				var vclock = util.vectorClockFromItem(item).pop();
				var rprefJob = RPrefModificationJob.CreateWithUpdatedActionFromRPref(
					item.id.S, item.rangeId.S, vclock.last_modified, vclock.version, item
				);
				if (util.timeInTimeWindowTS(tw, 
						util.notificationTime(_this.event.event_start, rprefJob.rpref)
				)) {
					hasStuffToExecute = true;
					concurrent.add({
						target:{
							id:_this.event.event_id,
							assetTimeTS:_this.event.event_start,
							last_modified:_this.event.last_modified,
							version:_this.event.version,
							rprefJob:rprefJob
						},
						fnc:function (continue_callback) {
							_this._createPreparedAndTimelineItemsWithParams(this, function (err) {
								continue_callback(err === null, err);
							});
						}
					});
				}
			}
		}

		if (hasStuffToExecute) {
			concurrent.execute(function () {
				continue_callback(true);
				concurrent = null;
			});
		}
		else
			continue_callback(true);
	}, function (err) {
		// Abort
		callback(err);
		jobstream = null;
	});

	queryTableWithStream (this.Preferences, {S:id}, env.get('JobInternalBatchingSize'),
		jobstream, function() {
			// Complete
			callback(null);
			jobstream = null;
	});
}

EventModificationJob.prototype._createPreparedAndTimelineItemsWithParams = function (
	params, callback
) {
	var rprefJob = params.rprefJob;
	delete params.rprefJob;
	// poor man's serial
	rprefJob.createPreparedItemsFromParams([params], function (err, prepared, old) {
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

function queryTableWithStream (table, hashKeyValue, limit, jobstream, callback, ExclusiveStartKey) {
	var params = { HashKeyValue:hashKeyValue, ConsistentRead:true, ScanIndexForward:true };
	if (limit !== -1)
		params.Limit = limit;
	if (ExclusiveStartKey !== undefined)
		params.ExclusiveStartKey = ExclusiveStartKey;
	table.query(params).
		on('success', function (response) {
			var data = response.data;
			jobstream.stream(data.Items);
			if (data.LastEvaluatedKey === undefined || data.LastEvaluatedKey === null)
				jobstream.end(callback);
			else
				queryTableWithStream(
					table, hashKeyValue, limit, jobstream, callback,  data.LastEvaluatedKey
				);
		}).
		on('error', function (err) {
			jobstream.abort(err);
		}).
	send();
}



/**
*	Event deleted job
*/
EventModificationJob.prototype._runDeleted = function(callback) {
	function _breakOnError_ (err) {
		if (err === undefined || err === null) return true;
		//console.log('<<<<<<<<Event Delete Job');
		callback(err); return false;
	}

	if (this.skipIsEvent)
		var au = new AssetUpdateReminderJob(this.event.event_id,'task');
	else
		var au = new AssetUpdateReminderJob(this.event.event_id,'event');

	//console.log('>>>>>>>>Event Delete Job');
	(new Serialize).
		then({
			target:this, 
			params:this.event.event_id, 
			fnc:this._deletePreferencesAndIndexItemsWithId
		}, _breakOnError_).
		then({
			target:au,
			params:null,
			fnc:au.run
		}, _breakOnError_).
		then(function () {
			//console.log('<<<<<<<<Event Delete Job');
			callback(null);
		}).
	execute();
}

EventModificationJob.prototype._deletePreferencesAndIndexItemsWithId = function (id, callback) {
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		var concurrent = new Concurrent(function (err) {
			continue_callback(false, err);
			concurrent = null;
		});

		var hasStuffToExecute = false;
		items = items.pop();
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i) {
				var item = items_i[j];
				if (util.garbageCollectionItemIsMarkedDeleted(item))
					continue;
				var vclock = util.vectorClockFromItem(item).pop();
				var rprefJob = RPrefModificationJob.CreateWithUpdatedActionFromRPref(
					item.id.S, item.rangeId.S, vclock.last_modified, vclock.version, item
				);
				hasStuffToExecute = true;
				concurrent.add({
					target:{rprefJob:rprefJob},
					fnc:function (continue_callback) {
						this.rprefJob.runDeleted(function (err) {
							continue_callback(err === null, err);
						});
					}
				});
			}
		}

		if (hasStuffToExecute) {
			concurrent.execute(function () {
				continue_callback(true);
				concurrent = null;
			});
		}
		else
			continue_callback(true);
	}, function (err) {
		// Abort
		callback(err);
		jobstream = null;
	});

	queryTableWithStream (this.Preferences, {S:id}, 4, jobstream, function() {
		// Complete
		callback(null);
		jobstream = null;
	});
}

module.exports = EventModificationJob;
