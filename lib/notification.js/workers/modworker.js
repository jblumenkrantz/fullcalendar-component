var ReminderModificationMessage = require('../queues/remmodmsg');
var ModificationJob = require('../jobs/modjob');
var modexceptions = require('../jobs/modexceptions');
var probe = require('../utils/probe').sharedInstance();
var env = require('../environemnt');

var ModificationWorker = function (queue) {
	//console.log("in constructor");
	this._jobPullingState = ModificationWorker.SQSNotPulling;
	this._jobPullPauseCommand = true;
	this._isCritical = false;
	this._queue = queue;
	this._numberOfCompletedJobs = 0;
	this._numberOfRunningJobs = 0;
	probe.maxLag(env.get('ProcessMaxLagMilliseconds'));
	var _this = this;
	setInterval(function () {
		var lag = probe.lag();

		//console.log('lag: '+lag+
		//	'; jobs running: '+_this._numberOfRunningJobs+
		//	'; completed jobs: '+_this._numberOfCompletedJobs
		//);

		if (lag == 0 && _this._numberOfRunningJobs > 0) {
			var prev_numberOfCompletedJobs = _this._numberOfCompletedJobs;
			var prev_numberOfRunningJobs = _this._numberOfRunningJobs;
			setTimeout(function () {
				if (
					probe.lag() == 0 &&
					_this._numberOfCompletedJobs == prev_numberOfCompletedJobs &&
					_this._numberOfRunningJobs == prev_numberOfRunningJobs
				) {
					console.log('Process Hang; Kill!');
					process.exit(1);
				}
			}, 1000);
		}
		
	}, 5000);
}
ModificationWorker.SQSPulling = 'SQSPulling';
ModificationWorker.SQSNotPulling = 'SQSNotPulling';

ModificationWorker.prototype.run = function () {
	//console.log("run is running");
	this.resumeJobPulling();
	return this;
};

ModificationWorker.prototype.resumeJobPulling = function () {
	//console.log("resumeJobPulling is running.");
	if (this._jobPullPauseCommand === true) {
		this._jobPullPauseCommand = false;
		if (this._jobPullingState === ModificationWorker.SQSNotPulling)
			this.jobPull();
	}
}

ModificationWorker.prototype.pauseJobPulling = function () {
	if (this._jobPullPauseCommand === false)
		this._jobPullPauseCommand = true;
}

ModificationWorker.prototype.isJobPulling = function () {
	return this._jobPullingState === ModificationWorker.SQSPulling;
}

ModificationWorker.prototype.jobPull = function () {
	var _this = this;
	this._jobPullingState = ModificationWorker.SQSPulling;
	//console.log("jobPull is running.");
	(this._queue.receiveModificationMessage({VisibilityTimeout:20})).
		on('data', function (modmsgs) {
			//console.log("On data is happening.");
			if (modmsgs && modmsgs.length > 0) {
				for (var i in modmsgs) {
					try {
						_this.runJobs(modmsgs[i]);
					} catch (err) {/* silent error */}
				}
			}
		}).
		on('error', function (response) {console.log("on error is happening.");}).
		on('complete', function (response) {
			//console.log("on complete is happening.");
			if (probe.isCriticalLag() && _this._numberOfRunningJobs > 0) {
				_this._isCritical = true;
			}
			else if (_this._jobPullPauseCommand)
				_this._jobPullingState = ModificationWorker.SQSNotPulling;
			else
				_this.jobPull();
		}).
	send();
}

ModificationWorker.prototype.isCriticalJobPulling = function () {
	return this._isCritical;
}

ModificationWorker.prototype.resumeJobPullingFromCritical = function () {
	if (this._isCritical === true) {
		this._isCritical = false;
		if (this._jobPullPauseCommand === false)
			this.jobPull();
	}
}

ModificationWorker.prototype.runJobs = function (msg) {
	
	var _this = this;
	// visibility maintenance
	var timer = setInterval(function () {
		ReminderModificationMessage.changeVisiblity(msg, msg.visibilityTimeout, _this._queue);
	}, Math.floor(msg.visibilityTimeout * 500));
	// run job
	this._numberOfRunningJobs++;
	(new ModificationJob(msg)).run(function (err) {
		clearInterval(timer);
		if (err !== null) {
			if (
				typeof err === 'object' &&
				err.code !== undefined &&
				err.code === modexceptions.ModificationJobDescriptionMalformation
			) {
				console.dir(err);
				ReminderModificationMessage.delete(msg, _this._queue);
			} else {
				console.dir(err);
				process.exit(1);
			}
		}
		else {
			_this._numberOfCompletedJobs++;
			_this._numberOfRunningJobs--;
			ReminderModificationMessage.delete(msg, _this._queue);
			if (
				_this.isCriticalJobPulling() &&
				(_this._numberOfRunningJobs === 0 || !probe.isCriticalLag())
			)
				_this.resumeJobPullingFromCritical();
		}
	});
}

module.exports = ModificationWorker;
