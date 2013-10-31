var mysql = require('../utils/wmysqlw');
var util = require("./jobutil");

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

var TimelineJob = function (jobmsg) {
	this.jobmsg = jobmsg;
}

TimelineJob.prototype.run = function (callback) {
	var assetId = this.jobmsg.assetId.S;
	var rprefId = this.jobmsg.rangeId.S.split(':')[1];
	//console.log("running sanity check...");
	// final sanity check
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
			t.version as t_version,\
			u.timezone\
		FROM reminder_prefs r\
		JOIN users u\
			ON u.user_id = r.user_id\
		LEFT JOIN calendar_subs s\
			ON r.aggregate = 1\
				AND r.calendar_id = s.calendar_id\
				AND r.user_id = s.user_id\
		LEFT JOIN events e\
			ON (\
					(r.aggregate = 1 AND e.calendar_id = s.calendar_id AND e.event_id='"+assetId+"')\
					OR\
					(r.aggregate = 0 AND e.event_id = r.event_id)\
				)\
		LEFT JOIN tasks t\
			ON (\
					(r.aggregate = 1 AND t.calendar_id = s.calendar_id AND t.task_id='"+assetId+"')\
					OR\
					(r.aggregate = 0 AND t.task_id = r.task_id)\
				)\
		WHERE\
			r.active = 1 AND r.reminder_pref_id='"+rprefId+"'\
			AND u.user_id = '"+this.jobmsg.userId.S+"' AND u.active = 1\
			AND (\
				(e.active = 1 AND e.event_id='"+assetId+"')\
				OR\
				(t.active = 1 AND t.task_id='"+assetId+"')\
			)\
	";
	var _this = this;
	//console.log(query);
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
			var returnedResult = false;
			((connection.query(query)).
				on('error', function (err) {
					callback({
						code:err.code,
						name:'MySQL Error',
						message:'Error was encounter while attempting to query the MySQL database',
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {
					if (!returnedResult) {
						returnedResult = true;
						
						var timeToUse = "";
						var thisAssetType = "";
						
						//console.log(_this.jobmsg);
						
						if (row.event_reminder_pref == true) {
							if (row.aggregate == "1") {
								if (row.event_id == null) {
									timeToUse = row.due_time;
									thisAssetType = "task";
								} else if (row.task_id == null) {
									timeToUse = row.event_start;
									thisAssetType = "event";
								}
							} else {
								timeToUse = row.event_start;
								thisAssetType = "event";
							}
						} else {
							timeToUse = row.due_time;
							thisAssetType = "task";
						}
						var timeTS = util.notificationTime(
							timeToUse,
							row
						);
						//console.log(timeTS+":"+timeToUse);
						if (timeTS == _this.jobmsg.id.N) {
							_this._sendMessageWithRow(row, thisAssetType, callback);
						} else {

							//console.log("SANITY CHECK FAIL");
								
							callback(null);
						}
					}
				}).
				on('end', function () {
					connection.destroy();
					if (!returnedResult)
						callback(null);
				})
			);
		}
	});
}

TimelineJob.prototype._sendMessageWithRow = function (row, assetType, callback) {
	//console.dir('this is where you send the message');
	
	var reminderItem = {
		userId:row.user_id,
		userTz:row.timezone,
		eventReminder:null,
		itemId:null
	}
	if (assetType == "event") {
		reminderItem.itemId = row.event_id;
		reminderItem.eventReminder = 1;
	} else if (assetType == "task") {
		reminderItem.itemId = row.task_id;
		reminderItem.eventReminder = 0;
	}
	
	//console.dir('calling getReminderInfo');		
	getReminderInfo(reminderItem, callback);
	

	callback(null);
}

function getReminderInfo (reminderItem, callback) {
	
	//console.dir("getReminderInfo running...");
	
	if (reminderItem.eventReminder == 1) {
		var query = "\
			SELECT\
				e.event_title,\
				UNIX_TIMESTAMP(e.event_start) as ts_event_start,\
				UNIX_TIMESTAMP(e.event_end) as ts_event_end,\
				e.allDay,\
				c.calendar_name,\
				o.org_name,\
				l.location_name,\
				op.opponent_name\
			FROM\
				events e\
			LEFT JOIN calendars c\
				ON e.calendar_id = c.calendar_id\
			LEFT JOIN public_calendars pc\
				ON e.calendar_id = pc.calendar_id\
			LEFT JOIN organizations o\
				ON pc.org_id = o.org_id\
			LEFT JOIN locations l\
				ON e.location_id = l.location_id\
			LEFT JOIN opponents op\
				ON e.opponent_id = op.opponent_id\
			WHERE\
				e.event_id = '"+reminderItem.itemId+"'\
		";
		
				
	} else if (reminderItem.eventReminder == 0) {
	
		var query = "\
			SELECT\
				t.task_name,\
				UNIX_TIMESTAMP(t.due_time) as ts_due_time,\
				c.calendar_name,\
				o.org_name\
			FROM\
				tasks t\
			LEFT JOIN calendars c\
				ON t.calendar_id = c.calendar_id\
			LEFT JOIN public_calendars pc\
				ON t.calendar_id = pc.calendar_id\
			LEFT JOIN organizations o\
				ON pc.org_id = o.org_id\
			WHERE\
				t.task_id = '"+reminderItem.itemId+"'\
		";
	
	} else {
		callback(null);
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
						message:'Error was encounter while attempting to query the MySQL database',
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {
					if (!returnedResult) {
						returnedResult = true;
						
						//console.dir(row);
						
						if (reminderItem.eventReminder == 1) {
							var reminderItemInfo = {
								userId:reminderItem.userId,
								userTz:reminderItem.userTz,
								itemType:'event',
								itemTitle:row.event_title,
								allDay:row.allDay,
								itemStart:row.ts_event_start,
								itemEnd:row.ts_event_end,
								calendarName:row.calendar_name,
								orgName:row.org_name,
								locationName:row.location_name,
								opponentName:row.opponent_name,
								itemId:reminderItem.itemId	
							};
						} else {
							var reminderItemInfo = {
								userId:reminderItem.userId,
								userTz:reminderItem.userTz,
								itemType:'task',
								itemTitle:row.task_name,
								calendarName:row.calendar_name,
								orgName:row.org_name,
								itemDue:row.ts_due_time,
								itemId:reminderItem.itemId
							}
						
						}
						
						// lookup contact points and issue reminder
						lookupContactPoints(reminderItemInfo,callback);
						
						// record reminder to reminder mod update log
						recordToReminderLog(reminderItemInfo,callback);
						
					}
				}).
				on('end', function () {
					connection.destroy();
					if (!returnedResult)
						callback(null);
				})
			);
		}
	});

}

function lookupContactPoints (reminderItemInfo, callback) {

	//console.log("lookupContactPoints running...");

	var uuid = require('node-uuid');
	var batchId = uuid.v1();
	var query = "\
		SELECT\
			point_type,\
			address,\
			country_code,\
			carrier_id\
		FROM\
			contact_points\
		WHERE\
			user_id = '"+reminderItemInfo.userId+"'\
		AND\
			emergency_only = 0\
		AND\
			activated_on IS NOT NULL\
	";
	
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
						message:'Error was encounter while attempting to query the MySQL database',
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {

					returnedResult = true;
					
					//console.dir(row);
					
					var thisAddress = "";
					if (row.point_type == "email")
						thisAddress = row.address;
					else
						thisAddress = row.country_code+row.address+"-"+row.carrier_id;
					
					sendNotificationOut(batchId,thisAddress,reminderItemInfo,callback);

				}).
				on('end', function () {
					connection.destroy();
					if (!returnedResult)
						callback(null);
				})
			);
		}
	});

}

function sendNotificationOut (batchId, formattedAddress, reminderItemInfo, callback) {

	//console.log("sending message...");
			
	var formattedMessage = reminderItemInfo.calendarName+": "+reminderItemInfo.itemTitle;
	if ((reminderItemInfo.orgName != '') && (reminderItemInfo.orgName != null))
		formattedMessage = reminderItemInfo.orgName+": "+formattedMessage;
		
	if (reminderItemInfo.itemType == 'event') {	
		var startTime = new timezoneJS.Date((reminderItemInfo.itemStart*1000),reminderItemInfo.userTz);
		var endTime = new timezoneJS.Date((reminderItemInfo.itemEnd*1000),reminderItemInfo.userTz);

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
			var dueTime = new timezoneJS.Date((reminderItemInfo.itemDue*1000),reminderItemInfo.userTz);
			formattedMessage += " Due "+dueTime.toString("M/d/yy H:mmk Z");
		}
	}
		
	var https = require('https');
	var querystring = require('querystring');
	var crypto = require('crypto');
	
	var shasum = crypto.createHash('sha1');
	shasum.update(formattedAddress+"rainbowkitties");
	
	var post_data = querystring.stringify({
		'BatchId' : batchId,
		'Destination': formattedAddress,
		'Subject': 'BRC Reminder',
		'PlainBody' : formattedMessage,
		'HtmlBody' : formattedMessage,
		'Auth' : shasum.digest('hex')
	});

	//console.log(post_data);

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
	});

	// post the data
	post_req.write(post_data);
	post_req.end();

	callback(null);

}

function recordToReminderLog (reminderItemInfo, callback) {

	//console.log("recordToReminderLog running...");
			
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
			var returnedResult = false;
			
			var nowTS = new timezoneJS.Date();
	
			var query = "\
				INSERT INTO\
					reminder_mod_updates\
					(reminder_mod_key,\
					original_time_sent,\
					last_modified,\
					user_id,\
					asset_title,\
					asset_id,\
					asset_type,\
					asset_start,\
					asset_end,\
					asset_due_time,\
					active)\
				VALUES\
					('"+reminderItemInfo.userId+":"+reminderItemInfo.itemId+"',\
					'"+nowTS.toString()+"',\
					'"+nowTS.toString()+"',\
					'"+reminderItemInfo.userId+"',\
					"+connection.escape(reminderItemInfo.itemTitle)+",\
					'"+reminderItemInfo.itemId+"',";
			
			if (reminderItemInfo.itemType == "event") {
				var startTS = new timezoneJS.Date(reminderItemInfo.itemStart*1000);
				var endTS = new timezoneJS.Date(reminderItemInfo.itemEnd*1000);		
				query += "'event',\
							'"+startTS.toString()+"',\
							'"+endTS.toString()+"',\
							null,";	
			} else if (reminderItemInfo.itemType == "task") {
				var dueTS = new timezoneJS.Date(reminderItemInfo.itemDue*1000);
				query += "'event',\
							null,\
							null,\
							'"+dueTS.toString()+"',";		
			}
	
			query += "1)\
			ON DUPLICATE KEY UPDATE\
				last_modified = '"+nowTS.toString()+"',\
				asset_title = "+connection.escape(reminderItemInfo.itemTitle)+",\
				";
				
			if (reminderItemInfo.itemType == "event") {
				var startTS = new timezoneJS.Date(reminderItemInfo.itemStart*1000);
				var endTS = new timezoneJS.Date(reminderItemInfo.itemEnd*1000);
				query += "asset_start = '"+startTS.toString()+"',\
							asset_end = '"+endTS.toString()+"'";
			} else if (reminderItemInfo.itemType == "task") {
				var dueTS = new timezoneJS.Date(reminderItemInfo.itemDue*1000);
				query += "asset_due_time = '"+dueTS.toString()+"'";			
			}
								
			
			((connection.query(query)).
				on('error', function (err) {
					callback({
						code:err.code,
						name:'MySQL Error',
						message:'Error was encounter while attempting to query the MySQL database',
						statusCode:400,
						retryable:true
					});
				}).
				on('result', function (row) {

					// nothing

				}).
				on('end', function () {
					connection.destroy();
					if (!returnedResult)
						callback(null);
				})
			);
		}
	});

}

module.exports = TimelineJob;
