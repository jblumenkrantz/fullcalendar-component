
var env = require('../environemnt');
var AWSQueue = require('../aws/awsqueue');
var util = require("util");
var AWSQueue = require('../aws/awsqueue');

var TimelineQueue = function (sqs, queueUrl) {
	TimelineQueue.super_.call(this, sqs, queueUrl);
}
util.inherits(TimelineQueue, AWSQueue);

TimelineQueue.prototype.receiveMessage = function (params, callback) {
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

/** Singleton **/
TimelineQueue._instance = null;
TimelineQueue.sharedInstance = function () {
	if (this._instance === null)
		this._instance = new TimelineQueue(null, null);
	return this._instance;
}

module.exports = TimelineQueue;
