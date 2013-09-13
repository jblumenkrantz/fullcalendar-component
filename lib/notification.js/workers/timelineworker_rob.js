var env = require('../environemnt');
var util = require('../jobs/jobutil');
var AWSDynamoTable = require('../aws/awsdynamodb');
var JobStream = require('../utils/jobstream');
var uuid = require('node-uuid');
var Concurrent = require('../utils/concurrent');
var TimelineJob = require('../jobs/timelinejob');

var TimelineWorker = function (queue) {
	this._queue = queue;
	this._timer = null;
	this.testTimerInASecond = false;
	this.Timeline = AWSDynamoTable.get('TimelineTable');
}

TimelineWorker.prototype.run = function () {
	this._startTimer();
	this.jobPull();
	return this;
}

TimelineWorker.prototype._startTimer = function () {
	if (this._timer !== null)
		clearTimeout(this._timer);
	var nowTS = parseInt(Date.now() / 1000);
	var dt = env.get('TimelineWorkerEvery___Minutes') * 60;
	var startTS = (parseInt(nowTS / dt) + 1) * dt;
	var _this = this;
	this._timer = setTimeout(function () {
		_this._alarm(startTS, dt);
		_this._timer = null;
		if (!_this.testTimerInASecond)
			_this._startTimer();
	}, this.testTimerInASecond? 3:(startTS - nowTS) * 1000);

}

TimelineWorker.prototype._alarm = function (nowTS, dt) {
	if (this.testTimerInASecond)
		nowTS = 1361885494;
	var _this = this;
	var jobstream = new JobStream(1, function (items, continue_callback) {
		// In Stream
		items = items.pop();
		var parsedItems = [];
		for (var i in items) {
			var items_i = items[i];
			for (var j in items_i)
				parsedItems.push(items_i[j])
		}
		if (parsedItems.length > 0) {
			_this._prepareTimelineJobs(parsedItems, function (err) {
				continue_callback(err === null, err)
			});
		} else
			continue_callback(true);
	}, function (err) {
		console.dir(err);
		process.exit(1);
	});

	scanTimelineWithStream(
		this.Timeline,
		nowTS-dt,
		nowTS+dt,
		env.get('TimelineJobBatchSize'),
		jobstream,
		function () {
			// Complete
			console.log('finished scan timeline');
			jobstream = null;
		}
	);
}

TimelineWorker.prototype._prepareTimelineJobs = function (items, callback) {
	var concurrent = new Concurrent(function (err) {
		callback(err);
		concurrent = null;
	});

	var _this = this;
	var hasStuffToExecute = false;
	for (var i in items) {
		var item = items[i];
		hasStuffToExecute = true;
		concurrent.add({
			target:{item:item},
			fnc:function (continue_callback) {
				_this._prepareTimelineJob(this.item, function (err) {
					continue_callback(err === null, err);
				});
			}
		})
	}

	if (hasStuffToExecute) {
		concurrent.execute(function () {
			callback(null);
			concurrent = null;
		});
	} else
		callback(null);
}

TimelineWorker.prototype._prepareTimelineJob = function (item, callback) {
	var doEnqueue = true;
	function _shouldPut_ (item, resource) {
		doEnqueue = resource.jobId.N == 0
		return false;
	};
	item.jobId = {S:'0'};
	item.state = {S:'preparing'}
	var _this = this;
	this.Timeline.syncCondPutItem(item,'id','rangeId',['jobId'],_shouldPut_, function (err, data) {
		if (err) {
			console.dir(err);
			process.exit(1);
		} else if (doEnqueue) {
			item.jobId = {S:uuid.v4()};
			_this._queue.sendMessage({MessageBody:JSON.stringify(item)}, function (err, data) {
				if (err) {
					console.dir(err);
					process.exit(1);
				}
			});
		}
	});
}

function scanTimelineWithStream(table, inf, sup, limit, jobstream, callback, ExclusiveStartKey) {
	var params = {
		ScanFilter:{id:{
			AttributeValueList:[{N:inf+''},{N:sup+''}],
			ComparisonOperator:'BETWEEN'
		}},
		ConsistentRead:true,
		ScanIndexForward:true
	};
	if (limit !== -1)
		params.Limit = limit;
	if (ExclusiveStartKey !== undefined)
		params.ExclusiveStartKey = ExclusiveStartKey;
	table.scan(params).
		on('success', function (response) {
			var data = response.data;
			jobstream.stream(data.Items);
			if (data.LastEvaluatedKey === undefined || data.LastEvaluatedKey === null)
				jobstream.end(callback);
			else
				scanTimelineWithStream(
					table, inf, sup, limit, jobstream, callback,  data.LastEvaluatedKey
				);
		}).
		on('error', function (err) {
			jobstream.abort(err);
		}).
	send();
}

TimelineWorker.prototype.jobPull = function () {
	var _this = this;
	var _visibiltyTimeout = 20;
	(this._queue.receiveMessage({
		MaxNumberOfMessages:env.get('TimelineJobBatchSize'), VisibilityTimeout:_visibiltyTimeout
	})).
		on('success', function (response) {
			var concurrent = new Concurrent(function (err) {
				console.dir(err);
				process.exit(1);
			});

			var hasStuffToExecute = false;
			if (response.data.Messages !== undefined) {
				var messages = response.data.Messages;
				for (var i in messages) {
					hasStuffToExecute = true;
					concurrent.add({
						target:{message:messages[i]},
						fnc:function (continue_callback) {
							var message = this.message;
							var body = JSON.parse(message.Body);
							if (
								body.id === undefined ||
								body.rangeId === undefined ||
								body.jobId === undefined
							) {
								_this.queue.deleteMessage({ReceiptHandle:message.ReceiptHandle}).send();
								continue_callback(true);
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
							}

							message.Body.state = {S:'started'};
							var params = { Item:message.Body, Expected: {'jobId': {Value: {S:'0'}}}};
							_this.Timeline.syncPutItem(params, 'id', 'rangeId', ['jobId'], _shouldPut_,
								function (err, data) {
									if (executeJob) {
										_this.runJob(message, function (err) {
											continue_callback(err === null, err);
										});
									} else
										continue_callback(err === null, err);
								}
							);
						}
					});
				}
			}

			if (hasStuffToExecute) {
				concurrent.execute(function () {
					_this.jobPull();
					concurrent = null;
				});
			}
			else
				_this.jobPull();
		}).
		on('error', function (err) {
			console.dir(err);
			process.exit(1);
		}).
	send();
}

TimelineWorker.prototype.runJob = function (msg, callback) {
	var _this = this;
	var timer = setInterval(function () {
		_this._queue.changeMessageVisibility({
			ReceiptHandle:msg.ReceiptHandle,
			VisibilityTimeout:msg.VisibilityTimeout
		}).send();
	}, Math.floor(msg.VisibilityTimeout * 500));
	console.log('>>>>>>>>TimelineWorker Job');
	(new TimelineJob(msg.Body)).run(function (err) {
		console.log('<<<<<<<<TimelineWorker Job');
		clearInterval(timer);
		if (!err) {
			var now = new Date();
			msg.Body.state = {S:'finished'};
			msg.Body.finishedAt = {S:now.toDateString()+' '+now.toTimeString()};
			_this.Timeline.putItem({Item:msg.Body}).on('success', function (response) {
				_this._queue.deleteMessage({ReceiptHandle:msg.ReceiptHandle}).send();
				callback(null);
			}).on('error', function (err) {
				callback(err);
			}).send();
		} else
			callback(err);
	});
}


module.exports = TimelineWorker;
