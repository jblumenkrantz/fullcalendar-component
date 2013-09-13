var ReminderModificationMessage = require('../queues/remmodmsg');
var modexceptions = require('./modexceptions');
var EventModificationJob = require('./eventmodjob');

var TaskModificationJob = function (modmsg) {
	this.modmsg = modmsg;
	this.validateTask(modmsg.object);
	this.task = modmsg.object;
}

TaskModificationJob.prototype.validateTask = function (task) {
	var mustExist = [
		'task_id',
		'calendar_id',
		'due_time',
		'last_modified',
		'version'
	];
	for (var i in mustExist) {
		if (task[mustExist[i]] === undefined)
			throw {
				code:modexceptions.ModificationJobDescriptionMalformation,
				name:modexceptions.ModificationJobDescriptionMalformation,
				message:'Reminder Perference is malformed.',
				statusCode:400,
				retryable:false
			};
	}
}

TaskModificationJob.prototype.run = function(callback) {
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

TaskModificationJob.prototype.runCreated = function(callback) {
	EventModificationJob.CreateWithCreateAction(
		this.task.task_id,
		this.task.calendar_id,
		this.task.due_time,
		this.task.last_modified,
		this.task.version,
		true
	).run(callback);
}

TaskModificationJob.prototype.runUpdated = function(callback) {
	EventModificationJob.CreateWithUpdatedAction(
		this.task.task_id,
		this.task.calendar_id,
		this.task.due_time,
		this.task.last_modified,
		this.task.version,
		true
	).run(callback);
}

TaskModificationJob.prototype.runDeleted = function(callback) {
	EventModificationJob.CreateWithDeleteAction(
		this.task.task_id,
		this.task.calendar_id,
		this.task.due_time,
		this.task.last_modified,
		this.task.version,
		true
	).run(callback);
}

module.exports = TaskModificationJob;
