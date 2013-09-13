
var Environment = function () {
	this._environemnt = {};
}

Environment.prototype.set = function (key, value) {
	this._environemnt[key] = value;
	return this;
}

Environment.prototype.get = function (key) {
	value = null;
	if (this._environemnt[key] !== undefined)
		value = this._environemnt[key];
	else if (process.env[key] !== undefined)
		value = process.env[key];
	return value;
}

Environment.prototype.load = function (script, callback) {
	require(script);
	if (callback !== undefined) callback();
	return this;
}

Environment._instance = null;
Environment.sharedInstance = function () {
	if (this._instance === null)
		this._instance = new Environment;
	return this._instance;
}
module.exports = Environment.sharedInstance();
