var env      = require('../environemnt').load('./etc.d/config.js');
var AWSQueue = require('../aws/awsqueue');
var util     = require("util");
var events   = require("events");
var ReminderModificationMessage = require('./remmodmsg');

var ReminderModificationQueue = function (sqs, queueUrl) {
	ReminderModificationQueue.super_.call(this, sqs, queueUrl);
}
util.inherits(ReminderModificationQueue, AWSQueue);

ReminderModificationQueue.prototype.receiveMessage = function (params, callback) {
	// Parameters
	callback = callback || null;
	params = params || {};
	if (params.VisibilityTimeout === undefined) params.VisibilityTimeout = 32;
	params.MaxNumberOfMessages = params.MaxNumberOfMessages || env.get('SQSMaxNumberOfMessages') || 10;
	if (params.WaitTimeSeconds === undefined) {
		params.WaitTimeSeconds = params.WaitTimeSeconds = env.get('SQSReceiveMessageWaitTimeSeconds');
		if (params.WaitTimeSeconds === null)
			params.WaitTimeSeconds = 10;
	}
	// Request
	return AWSQueue.prototype.receiveMessage.call(this, params, callback);
}

ReminderModificationQueue.prototype.receiveModificationMessage = function (params, callback) {
	params = params || {};
	if (params.VisibilityTimeout === undefined) params.VisibilityTimeout = 32;
	var _this = this;
	if (callback) {
		return this.receiveMessage(params, function (err, data) {
			callback(err, data? _this.handleMessages(data, params.VisibilityTimeout): []);
		});
	}
	var request = this.receiveMessage(params);
	(request).
		on('success', function (response) {
			request.emit('data',
				response.data !== undefined
					?_this.handleMessages(response.data, params.VisibilityTimeout)
					:[]
			);
		});
	return request;
}


/** Parse Queued Messages Safely */
ReminderModificationQueue.prototype.handleMessageMalformation = function (receiptHandle) {
	if (typeof receiptHandle === 'string')
		this.deleteMessage({ReceiptHandle:receiptHandle}).send();
}

ReminderModificationQueue.prototype.unpackSQSMessage = function (data) {
	var messages_out = [];
	if (data && data.Messages !== undefined) {
		var messages_in = data.Messages;
		for (var i in messages_in) {
			var message_in = messages_in[i];
			if (message_in.ReceiptHandle !== undefined) {
				if (message_in.Body !== undefined) {
					messages_out.push({ReceiptHandle:message_in.ReceiptHandle, Body:message_in.Body});
				} else
					this.handleMessageMalformation(message_in.ReceiptHandle);
			}
		}
	}
	return messages_out;
}

ReminderModificationQueue.prototype.unpackModificationQueueMessage = function (queueMessage) {
	if (!queueMessage) throw 'null exception';
	var message_in = JSON.parse(queueMessage.Body);
	var message_out = null;
	if (message_in.type !== undefined && message_in.message !== undefined) {
		message_out = {
			ReceiptHandle:queueMessage.ReceiptHandle,
			type:message_in.type,
			message:message_in.message
		};
	}
	else
		this.handleMessageMalformation(queueMessage.ReceiptHandle);
	return message_out;
}

ReminderModificationQueue.prototype.unpackModificationMessage = function (modificationMessage) {
	if (!modificationMessage) throw 'null error';
	var message_in = JSON.parse(modificationMessage.message);
	var message_out = null;
	if (message_in.type !== undefined) {
		var object = null;
		for (var key in message_in) {
			if (key !== 'type')
				{ object = message_in[key]; break; }
		}
		if (object !== null ) {
			message_out = {
				ReceiptHandle:modificationMessage.ReceiptHandle,
				type:modificationMessage.type,
				action:message_in.type,
				object:object
			};
		} else
			this.handleMessageMalformation(modificationMessage.ReceiptHandle);
	} else
		this.handleMessageMalformation(modificationMessage.ReceiptHandle);
	return message_out;
}

ReminderModificationQueue.prototype.handleMessages = function (data, visibilityTimeout) {
	var queueMessages = this.unpackSQSMessage(data);
	var modifications = [];
	for (var i in queueMessages) {
		var message_in = queueMessages[i];
		try {
			var message_out = this.unpackModificationMessage(
				this.unpackModificationQueueMessage(message_in)
			);
			modifications.push(new ReminderModificationMessage (
				message_out.type,
				message_out.action,
				message_out.object,
				message_out.ReceiptHandle,
				visibilityTimeout,
				this
			));
		} catch (err) {
			this.handleMessageMalformation(message_in.ReceiptHandle);
		}
	}
	return modifications;
}

/** Singleton **/
ReminderModificationQueue._instance = null;
ReminderModificationQueue.sharedInstance = function () {
	if (this._instance === null)
		this._instance = new ReminderModificationQueue(null, null);
	return this._instance;
}

module.exports = ReminderModificationQueue;
