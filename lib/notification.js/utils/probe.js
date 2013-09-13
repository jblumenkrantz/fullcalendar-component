
var Probe = function () {
	this._toobusy = require('toobusy');
}

Probe.prototype.maxLag = function (maxLag) {
	this._toobusy.maxLag(maxLag);
	return this;
}

Probe.prototype.lag = function () {
	return this._toobusy.lag();
}

Probe.prototype.isCriticalLag = function () {
	return this._toobusy();
}

Probe._instance = null;
Probe.sharedInstance = function () {
	if (Probe._instance === null) {
		Probe._instance = new Probe;
	}
	return Probe._instance;
}

module.exports = Probe;