
var AWSQueue = function (sqs, queueUrl) {
	this.sqs = sqs;
	this.queueUrl = queueUrl;
}

AWSQueue.prototype._call = function (target, fnc, params, callback) {
	params = params || {};
	callback = callback || null;
	params.QueueUrl = this.queueUrl;
	return fnc.call(target, params, callback);
}

AWSQueue.prototype.addPermission = function (params, callback) {
	return this._call(this.sqs, this.sqs.addPermission, params, callback);
}

AWSQueue.prototype.changeMessageVisibility = function (params, callback) {
	return this._call(this.sqs, this.sqs.changeMessageVisibility, params, callback);
}

AWSQueue.prototype.changeMessageVisibilityBatch = function (params, callback) {
	return this._call(this.sqs, this.sqs.changeMessageVisibilityBatch, params, callback);
}

AWSQueue.prototype.deleteMessage = function (params, callback) {
	return this._call(this.sqs, this.sqs.deleteMessage, params, callback);
}

AWSQueue.prototype.deleteMessageBatch = function (params, callback) {
	return this._call(this.sqs, this.sqs.deleteMessageBatch, params, callback);
}

AWSQueue.prototype.deleteQueue = function (params, callback) {
	return this._call(this.sqs, this.sqs.deleteQueue, params, callback);
}

AWSQueue.prototype.getQueueAttributes = function (params, callback) {
	return this._call(this.sqs, this.sqs.getQueueAttributes, params, callback);
}

AWSQueue.prototype.receiveMessage = function (params, callback) {
	return this._call(this.sqs, this.sqs.receiveMessage, params, callback);
}

AWSQueue.prototype.removePermission = function (params, callback) {
	return this._call(this.sqs, this.sqs.removePermission, params, callback);
}

AWSQueue.prototype.sendMessage = function (params, callback) {
	return this._call(this.sqs, this.sqs.sendMessage, params, callback);
}

AWSQueue.prototype.sendMessageBatch = function (params, callback) {
	return this._call(this.sqs, this.sqs.sendMessageBatch, params, callback);
}

AWSQueue.prototype.setQueueAttributes = function (params, callback) {
	return this._call(this.sqs, this.sqs.setQueueAttributes, params, callback);
}

module.exports = AWSQueue;
