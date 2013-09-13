var bridge = require('../bridge');

var ReminderModificationMessage = function (type, action, object, receiptHandle, visibilityTimeout) {
	switch (type) {
		// Task
		case bridge.get(bridge.keys.EventModificationType):
			this.type = ReminderModificationMessage.EventType;
			break;
		// Event
		case bridge.get(bridge.keys.TaskModificationType):
			this.type = ReminderModificationMessage.TaskType;
			break;
		// Reminder Preference
		case bridge.get(bridge.keys.ReminderPrefModificationType):
			this.type = ReminderModificationMessage.ReminderPrefType;
			break;
		default:
			this.type = ReminderModificationMessage.UnknownType;
	}
	switch (action) {
		// Created
		case bridge.get(bridge.keys.EventModificationCreatedAction):
		case bridge.get(bridge.keys.TaskModificationCreatedAction):
		case bridge.get(bridge.keys.ReminderPrefModificationCreatedAction):
			this.action = ReminderModificationMessage.CreatedAction;
			break;
		// Updated
		case bridge.get(bridge.keys.EventModificationUpdatedAction):
		case bridge.get(bridge.keys.TaskModificationUpdatedAction):
		case bridge.get(bridge.keys.ReminderPrefModificationUpdatedAction):
			this.action = ReminderModificationMessage.UpdatedAction;
			break;
		// Deleted
		case bridge.get(bridge.keys.EventModificationDeletedAction):
		case bridge.get(bridge.keys.TaskModificationDeletedAction):
		case bridge.get(bridge.keys.ReminderPrefModificationDeletedAction):
			this.action = ReminderModificationMessage.DeletedAction;
			break;
		default:
			this.action = ReminderModificationMessage.UnknownAction;
	}
	this.object = object;
	this.receiptHandle = receiptHandle;
	this.visibilityTimeout = visibilityTimeout;
}
ReminderModificationMessage.EventType = 'ReminderModificationMessage.EventType';
ReminderModificationMessage.TaskType = 'ReminderModificationMessage.TaskType';
ReminderModificationMessage.ReminderPrefType = 'ReminderModificationMessage.ReminderPrefType';
ReminderModificationMessage.UnknownType = 'ReminderModificationMessage.UnknownType';
ReminderModificationMessage.CreatedAction = 'ReminderModificationMessage.CreatedAction';
ReminderModificationMessage.UpdatedAction = 'ReminderModificationMessage.UpdatedAction';
ReminderModificationMessage.DeletedAction = 'ReminderModificationMessage.DeletedAction';
ReminderModificationMessage.UnknownAction = 'ReminderModificationMessage.UnknownAction';


ReminderModificationMessage.changeVisiblity = function (messages, visiblity, queue) {
	if (!queue) return;
	if (typeof messages === 'string' || messages.receiptHandle !== undefined) {
		var _m = []; _m.push(messages);
		messages = _m;
	}
	if (typeof messages === 'object' && messages !== null) {
		var entries = [];
		for (var i in messages) {
			var receiptHandle = messages[i];
			if (receiptHandle.receiptHandle !== undefined) {
				if (receiptHandle.visibilityTimeout !== undefined)
					receiptHandle.visibilityTimeout = visiblity;
				receiptHandle = receiptHandle.receiptHandle;
			}
			if (typeof receiptHandle === 'string') {
				entries.push({
					Id:i,
					ReceiptHandle:receiptHandle,
					VisibilityTimeout:visiblity
				});
			}
		}
		if (entries.length > 0)
			queue.changeMessageVisibilityBatch({Entries:entries}).send();
	}
}

ReminderModificationMessage.delete = function (messages, queue) {
	if (!queue) return;
	if (typeof messages === 'string' || messages.receiptHandle !== undefined) {
		var _m = []; _m.push(messages);
		messages = _m;
	}
	if (typeof messages === 'object' && messages !== null) {
		var entries = [];
		for (var i in messages) {
			var receiptHandle = messages[i];
			if (receiptHandle.receiptHandle !== undefined)
				receiptHandle = receiptHandle.receiptHandle;
			if (typeof receiptHandle === 'string') {
				entries.push({Id:i, ReceiptHandle:receiptHandle});
			}
		}
		if (entries.length > 0)
			queue.deleteMessageBatch({Entries:entries}).send();
	}
}

module.exports = ReminderModificationMessage;
