var mysql = require('../utils/wmysqlw');
var JobStream = require('../utils/jobstream');
var env = require('../environemnt');
var Serialize = require('../utils/serialize');
var https = require('https');
var querystring = require('querystring');
var crypto = require('crypto');
var uuid = require('node-uuid');

var timezoneJS = require('../../../www/lib/timezone');
var fs = require('fs');
timezoneJS.timezone.zoneFileBasePath = '../../www/tz';
timezoneJS.timezone.transport = function (opts) {
// No success handler, what's the point?
if (opts.async) {
	if (typeof opts.success !== 'function') return;
	opts.error = opts.error || console.error;
	return fs.readFile(opts.url, 'utf8', function (err, data) {
	return err ? opts.error(err) : opts.success(data);
	});
}
	return fs.readFileSync(opts.url, 'utf8');
};

timezoneJS.timezone.init({async:false});

var AssetUpdateReminderJob = function (assetId, assetType) {
	//console.log("au constructor");

	this.assetId = assetId;
	this.assetType = assetType;
	this.freshDetails = {};
	this.usersToUpdate = [];

}

AssetUpdateReminderJob.prototype.run = function (callback) {
	
	//console.log("au run");
	
	function _breakOnError_ (err) {
		if (err === undefined || err === null) return true;
		console.log('AU SERIALIZE ERROR!');
		callback(err); return false;
	}	
	
	(new Serialize).
		then({
			target:this,
			fnc:this._loadFreshAssetDetails
		}, _breakOnError_).
		then({
			target:this,
			fnc:this._comparePreviousReminders
		}, _breakOnError_).
		then({
			target:this,
			fnc:this._issueUpdates
		}, _breakOnError_).
		then({
			target:this,
			fnc:this._updateLogWithFreshAssetDetails
		}, _breakOnError_).
		then(function () {
			//console.log('DONE RUNNING AssetUupdatReminderJob');
			callback(null);
		}).
	execute();	
	
	return this;
}

AssetUpdateReminderJob.prototype._loadFreshAssetDetails = function (callback) {
	//console.log("loadFreshAssetDetails");
	var _this = this;
	if (this.assetType == "event") {
			var query = "\
				SELECT\
					ev.event_title,\
					UNIX_TIMESTAMP(ev.event_start) as ts_event_start,\
					UNIX_TIMESTAMP(ev.event_end) as ts_event_end,\
					ev.all_day,\
					c.calendar_name,\
					o.org_name,\
					l.location_name,\
					op.opponent_name,\
					ev.active\
				FROM\
					events ev\
				LEFT JOIN calendars c\
					ON ev.calendar_id = c.calendar_id\
				LEFT JOIN public_calendars pc\
					ON ev.calendar_id = pc.calendar_id\
				LEFT JOIN organizations o\
					ON pc.org_id = o.org_id\
				LEFT JOIN locations l\
					ON ev.location_id = l.location_id\
				LEFT JOIN opponents op\
					ON ev.opponent_id = op.opponent_id\
				WHERE\
					ev.event_id = '"+this.assetId+"'";
	} else if (this.assetType == "task") {
			var query = "\
				SELECT\
					t.task_name,\
					UNIX_TIMESTAMP(t.due_time) as ts_due_time,\
					c.calendar_name,\
					o.org_name,\
					t.active\
				FROM\
					tasks t\
				LEFT JOIN calendars c\
					ON t.calendar_id = c.calendar_id\
				LEFT JOIN public_calendars pc\
					ON t.calendar_id = pc.calendar_id\
				LEFT JOIN organizations o\
					ON pc.org_id = o.org_id\
				WHERE\
					t.task_id = '"+this.assetId+"'";
	}
	
	mysql.getReadConnection(function (err, connection) {
		if (err) {
			callback({
				code:err.code,
				name:'MySQL Error',
				message:'Error was encounter while attempting to connect to MySQL database',
				statusCode:400,
				retryable:true
			});
		} else {
			var returnedResult = false;
			((connection.query(query)).
				on('error', function (err) {
					callback({
						code:err.code,
						name:'MySQL Error',
						message:'Error was encounter while attempting to query the MySQL database'+query,
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {
																					
					if (_this.assetType == "event") {
						_this.freshDetails = {
							itemTitle:row.event_title,
							itemStart:row.ts_event_start,
							itemEnd:row.ts_event_end,
							calendarName:row.calendar_name,
							orgName:row.org_name,
							locationName:row.location_name,
							opponentName:row.opponent_name,
							isActive:row.active,
							itemType:'event',
							allDay:row.all_day
						};
					} else if (_this.assetType == "task") {
						_this.freshDetails = {
							itemTitle:row.task_name,
							calendarName:row.calendar_name,
							orgName:row.org_name,
							itemDue:row.ts_due_time,
							isActive:row.active,
							itemType:'task'
						};					
					}
											
				}).
				on('end', function () {
					connection.destroy();
					callback(null);
				})
			);
		}
	});

}

AssetUpdateReminderJob.prototype._comparePreviousReminders = function (callback) {
	//console.log("comparePreviousReminders");
	var _this = this;
	var query = "\
		SELECT\
			r.user_id as user_id,\
			r.asset_title as asset_title,\
			UNIX_TIMESTAMP(r.asset_start) as asset_start,\
			UNIX_TIMESTAMP(r.asset_end) as asset_end,\
			UNIX_TIMESTAMP(r.asset_due_time) as asset_due_time,\
			r.active as active,\
			u.timezone as timezone,\
			cp.point_type as point_type,\
			cp.address as address,\
			cp.country_code as country_code,\
			cp.carrier_id as carrier_id\
		FROM\
			reminder_mod_updates r\
		LEFT JOIN\
			users u ON r.user_id = u.user_id\
		LEFT JOIN\
			contact_points cp ON r.user_id = cp.user_id\
		WHERE\
			r.asset_id = '"+_this.assetId+"'\
		AND\
			cp.emergency_only = 0";
			
	mysql.getReadConnection(function (err, connection) {
		if (err) {
			callback({
				code:err.code,
				name:'MySQL Error',
				message:'Error was encounter while attempting to connect to MySQL database in AssetUpdateReminderJob',
				statusCode:400,
				retryable:true
			});
		} else {
			var jobstream = new JobStream(env.get('UpdateJobBatchSize'),
				function (rows, continue_callback) {
					// Stream
					connection.pause();
					
					_this._determineUsersFromRows(rows, function(err) {				
						connection.resume();
						if (err)
							continue_callback(false,err);
						else
							continue_callback(true);
					});	
										
				},
				function (err) {
					// Abort
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
							message:'Error was encounter while attempting to query the MySQL database'+query,
							statusCode:400,
							retryable:true
						});
				}).
				on('result', function (rows) {
					if (jobstream) jobstream.stream(rows);
				}).
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

AssetUpdateReminderJob.prototype._determineUsersFromRows = function (rows, callback) {
	//console.log("determineUsersFromRows");
	var _this = this;
	
	
	for (var i in rows) {
		var rows_i = rows[i];
		for (var j in rows_i) {
			var row = rows_i[j];
			
			var fireUpdate = false;
			if (_this.assetType == "event") {
				if (row.asset_start != _this.freshDetails.itemStart)
					var fireUpdate = true;
				if (row.asset_end != _this.freshDetails.itemEnd)
					var fireUpdate = true;					
			} else if (_this.assetType == "task") {
				if (row.asset_due_time != _this.freshDetails.itemDue)
					var fireUpdate = true;					
			}
			if (row.active != _this.freshDetails.isActive)
				var fireUpdate = true;					
	
			if (fireUpdate) {
				
				var thisAddress = "";
				if (row.point_type == "email")
					thisAddress = row.address;
				else
					thisAddress = row.country_code+row.address+"-"+row.carrier_id;
				var thisUsrObj = {userId:row.user_id,userTz:row.timezone,userAddress:thisAddress};

				_this.usersToUpdate.push(thisUsrObj);
			}							
			
		}
					
	}
	
	callback(null);
	
}

AssetUpdateReminderJob.prototype._issueUpdates = function (callback) {
	//console.log("issueUpdates");
	var _this = this;
		
	if (_this.usersToUpdate.length > 0) { // check if there are updates to issue	
		
		var reminderItemInfo = _this.freshDetails;
			
		var formattedMessage = "";
		var batchId = uuid.v1();
	
		for (var x in _this.usersToUpdate) {
	
			var userInfo = _this.usersToUpdate[x];
	
			if (reminderItemInfo.isActive == 1)
				formattedMessage = "UPDATE: ";
			else
				formattedMessage = "CANCELED: ";
	
			if ((reminderItemInfo.orgName != '') && (reminderItemInfo.orgName != null))
				formattedMessage = reminderItemInfo.orgName+": ";
	
			formattedMessage += reminderItemInfo.calendarName+": "+reminderItemInfo.itemTitle;
				
			if (reminderItemInfo.itemType == 'event') {	
				var startTime = new timezoneJS.Date((reminderItemInfo.itemStart*1000),userInfo.userTz);
				var endTime = new timezoneJS.Date((reminderItemInfo.itemEnd*1000),userInfo.userTz);

				if (reminderItemInfo.allDay == 0) {
					formattedMessage += " "+startTime.toString("M/d/yy H:mmk");
					if (startTime.toString("M/d/yy") == endTime.toString("M/d/yy"))
						formattedMessage += "-"+endTime.toString("H:mmk Z");
					else
						formattedMessage += " - "+endTime.toString("M/d/yy H:mmk Z");
				} else {
					formattedMessage += " "+startTime.toString("M/d/yy");
					if (startTime.toString("M/d/yy") != endTime.toString("M/d/yy"))
						formattedMessage += "-"+endTime.toString("M/d/yy");
				}
			} else if (reminderItemInfo.itemType == 'task') {
				if (reminderItemInfo.itemDue != 0) {
					var dueTime = new timezoneJS.Date((reminderItemInfo.itemDue*1000),userInfo.userTz);
					formattedMessage += " Due "+dueTime.toString("M/d/yy H:mmk Z");
				}
			}
			
			var shasum = crypto.createHash('sha1');
			shasum.update(userInfo.userAddress+"rainbowkitties");
				
			var post_data = querystring.stringify({
				'BatchId' : batchId,
				'Destination': userInfo.userAddress,
				'Subject': 'BRC Update',
				'PlainBody' : formattedMessage,
				'HtmlBody' : formattedMessage,
				'Auth' : shasum.digest('hex')
			});

		
			// An object of options to indicate where to post to
			var post_options = {
				host: 'messenger-brc.sdicgdev.com',
				port: '443',
				path: '',
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'Content-Length': post_data.length
				}
			};

			// Set up the request
			var post_req = https.request(post_options, function(res) {
				res.setEncoding('utf8');
				res.on('data', function (chunk) {
					//console.log('Response: ' + chunk);
				});
				res.on('error', function(err) {
					callback({
						code:err.code,
						name:'MySQL Error',
						message:'Error was encountered while attempting to send post msg: '+err.message,
						statusCode:400,
						retryable:true
					});
				});
			});

			// post the data
			post_req.write(post_data);
			post_req.end();
		

		} // end user loop	
		
		callback(null);

	} else { // no reminders to update

		//console.log("Nobody to update.");
		callback(null);
		
	}

}

AssetUpdateReminderJob.prototype._updateLogWithFreshAssetDetails = function (callback) {

	//console.log("updateLogWithFreshAssetDetails");
	var _this = this;
	var reminderItemInfo = _this.freshDetails;	
	
	var nowTS = new timezoneJS.Date();
	
	
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

			
			var query = "\
				UPDATE\
					reminder_mod_updates\
				SET\
					last_modified = '"+nowTS.toString()+"',\
					asset_title = "+connection.escape(reminderItemInfo.itemTitle)+",\
					active = "+reminderItemInfo.isActive+",";
			
			if (reminderItemInfo.itemType == "event") {
				var startTS = new timezoneJS.Date(reminderItemInfo.itemStart*1000);
				var endTS = new timezoneJS.Date(reminderItemInfo.itemEnd*1000);		
				query += "asset_start = '"+startTS.toString()+"',\
							asset_end = '"+endTS.toString()+"'";
			} else if (reminderItemInfo.itemType == "task") {
				var dueTS = new timezoneJS.Date(reminderItemInfo.itemDue*1000);
				query += "asset_due_time = '"+dueTS.toString()+"'";
			}
					
			query += "WHERE\
						asset_id = '"+_this.assetId+"'";


			((connection.query(query)).
				on('error', function (err) {
					callback({
						code:err.code,
						name:'MySQL Error',
						message:'Error was encounter while attempting to query the MySQL database'+query,
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {
					
					// nothing to do																
																
				}).
				on('end', function () {
					connection.destroy();
					callback(null);
				})
			);
		}
	});

}

module.exports = AssetUpdateReminderJob;