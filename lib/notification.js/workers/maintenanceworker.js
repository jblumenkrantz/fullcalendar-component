var env = require('../environemnt');
var util = require('../jobs/jobutil');
var AWSDynamoTable = require('../aws/awsdynamodb');
var uuid = require('node-uuid');
var MaintenanceJob = require('../jobs/maintenancejob');


var MaintenanceWorker = function (queue) {
	this._queue = queue;
	this._timer = null;
	this.testTimerInASecond = false;
	this.Maintenance = AWSDynamoTable.get('Maintenance');
}

MaintenanceWorker.prototype.run = function () {
	this._startTimer();
	this.jobPull();
	return this;
};

MaintenanceWorker.prototype._startTimer = function () {
	if (this._timer !== null)
		clearTimeout(this._timer);
	var exerciseHour = env.get('MaintenanceWorkerHour');
	var now = new Date();
	var exerciseTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), exerciseHour, 0, 0);
	if (this.testTimerInASecond)
		exerciseTime = new Date(
			now.getFullYear(), now.getMonth(),
			now.getDate(), now.getHours(),
			now.getMinutes(),
			now.getSeconds() + 3);
	if (now > exerciseTime)
		exerciseTime = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, exerciseHour, 0, 0);
	var dt_milli = exerciseTime - now;
	var _this = this;
	this._timer = setTimeout(function () {
		_this._alarm(exerciseTime);
		_this._timer = null;
	}, dt_milli);
}

MaintenanceWorker.prototype._alarm = function (now) {
	function _shouldPut_ (item, resource) {return false;};
	var item = {
		id: {S:now.toDateString()+' '+now.toTimeString()},
		rangeId: {S:'0'},
		jobId: {S:'0'},
		state: {S:'preparing'}
	};
	var _this = this;
	this.Maintenance.syncCondPutItem(item,'id','rangeId',['id'],_shouldPut_, function (err, data) {
		if (err) {
			console.dir(err);
			process.exit(1);
		}
		else {
			_this._queue.sendMessage({MessageBody:JSON.stringify({
				id:item.id.S,
				jobId:uuid.v4()
			})}, function (err, data) {
				if (err) {
					console.dir(err);
					process.exit(1);
				}
			});
		}
	});
}

MaintenanceWorker.prototype.jobPull = function () {
	var _this = this;
	var _visibiltyTimeout = 20;
	(this._queue.receiveMessage({MaxNumberOfMessages:1, VisibilityTimeout:_visibiltyTimeout})).
		on('success', function (response) {
			if (response.data.Messages !== undefined) {
				var message = response.data.Messages.pop();
				var body = JSON.parse(message.Body);
				if (body.id === undefined || body.jobId === undefined) {
					_this.queue.deleteMessage({ReceiptHandle:message.ReceiptHandle}).send();
					return;
				}
				message.Body = body;
				message.VisibilityTimeout = _visibiltyTimeout;

				var executeJob = true;
				function _shouldPut_ (item, resource) {
					if (item.jobId.S !== resource.jobId.S) {
						_this._queue.deleteMessage({ReceiptHandle:message.ReceiptHandle}).send();
						executeJob = false;
					}	
					return false;
				};
				var item = {
					id: {S:body.id},
					rangeId: {S:'0'},
					jobId: {S:body.jobId},
					state: {S:'started'}
				};
				var params = { Item:item, Expected: {'jobId': {Value: {S:'0'}}}};
				_this.Maintenance.syncPutItem(params, 'id', 'rangeId', ['jobId'], _shouldPut_,
					function (err, data) {
						if (err) {
							console.dir(err);
							process.exit(1);
						}
						else if (executeJob) {
							_this.runJob(message);
						}
					}
				);
			}
		}).
		on('error', function (err) {}).
		on('complete', function (response) {
			_this.jobPull();
		}).
	send();
}

MaintenanceWorker.prototype.runJob = function (msg) {
	var _this = this;
	var timer = setInterval(function () {
		_this._queue.changeMessageVisibility({
			ReceiptHandle:msg.ReceiptHandle,
			VisibilityTimeout:msg.VisibilityTimeout
		}).send();
	}, Math.floor(msg.VisibilityTimeout * 500));
	//console.log('>>>>>>>>MaintenanceWorker Job');
	(new MaintenanceJob(msg.Body)).run(function (err) {
		//console.log('<<<<<<<<MaintenanceWorker Job');
		clearInterval(timer);
		if (!_this.testTimerInASecond)
			_this._startTimer();
		if (err) {
			console.dir(err);
			process.exit(1);
		}
		else {
			var now = new Date();
			_this.Maintenance.putItem({Item:{
				id: {S:msg.Body.id},
				rangeId: {S:'0'},
				jobId: {S:msg.Body.jobId},
				state: {S:'finished'},
				finishedAt: {S:now.toDateString()+' '+now.toTimeString()}
			}}).on('success', function (response) {
				_this._queue.deleteMessage({ReceiptHandle:msg.ReceiptHandle}).send();
			}).on('error', function (err) {
				console.dir(err);
				process.exit(1);
			}).send();
		}
	});
}

module.exports = MaintenanceWorker;
