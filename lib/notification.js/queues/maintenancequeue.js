
var env = require('../environemnt');
var AWSQueue = require('../aws/awsqueue');
var util = require("util");
var AWSQueue = require('../aws/awsqueue');

var MaintanceQueue = function (sqs, queueUrl) {
	MaintanceQueue.super_.call(this, sqs, queueUrl);
}
util.inherits(MaintanceQueue, AWSQueue);

MaintanceQueue.prototype.receiveMessage = function (params, callback) {
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
MaintanceQueue._instance = null;
MaintanceQueue.sharedInstance = function () {
	if (this._instance === null)
		this._instance = new MaintanceQueue(null, null);
	return this._instance;
}

module.exports = MaintanceQueue;

