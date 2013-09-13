var ReminderModificationMessage = require('../queues/remmodmsg');
var EventModificationJob = require('./eventmodjob');
var TaskModificationJob = require('./taskmodjob');
var RPrefModificationJob = require('./rprefmodjob');

var ModificationJob = function (modmsg) {
	this.modmsg = modmsg;
}

ModificationJob.prototype.run = function(callback) {
	try {
		if (typeof callback !== 'function')
			throw 'Callback must be defined and of type \'function\'';
		switch (this.modmsg.type) {
			case ReminderModificationMessage.EventType:
				(new EventModificationJob(this.modmsg)).run(callback);
				break;
			case ReminderModificationMessage.TaskType:
				(new TaskModificationJob(this.modmsg)).run(callback);
				break;
			case ReminderModificationMessage.ReminderPrefType:
				(new RPrefModificationJob(this.modmsg)).run(callback);
				break;
			default:
				callback({
					code:'UnrecognizedJobType',
					name:'UnrecognizedJobType',
					message:'Expected Event, Task, or Reminder type',
					statusCode:400,
					retryable:false
				});
				break;
		}
	} catch (err) {
		if (typeof callback === 'function')
			callback(err);
		else
			throw err;
	}
	return this;
};

module.exports = ModificationJob;
