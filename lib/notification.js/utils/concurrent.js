
var Concurrent = function (abortHandler) {
	this._abort = false;
	this._abortHandler = null;
	if (abortHandler)
		this._abortHandler = abortHandler;
	this._calls = [];
}

function _oshifta (o) {
	delete o['0'];
	var args_shift = [];
	for (var i in o) args_shift.push(o[i]);
	return args_shift;
}

Concurrent.prototype.add = function (call) {
	var type = typeof call;
	if (
		type === 'function' ||
		(type === 'object' && call.fnc !== undefined)
	)
		this._calls.push(call);
	return this;
}

Concurrent.prototype.execute = function (callback) {
	var _this = this;
	var calls = this._calls;
	var outstandingCount = calls.length;
	var _callback = function (shouldNotAbort) {
		if (!shouldNotAbort)
			_this.abort(_oshifta(arguments));
		else if (
			!_this._abort && --outstandingCount === 0 &&
			typeof callback == 'function'
		) {
			callback.apply(null, _oshifta(arguments));
		}
	}

	for (var i in calls) {
		if (_this._abort) break;
		var call = calls[i];
		var type = typeof call;
		if (type === 'function')
			call.call(null, _callback);
		else if (call.target === undefined) {
			if (call.params == undefined)
				call.fnc.call(call, _callback);
			else
				call.fnc.call(call, call.params, _callback);
		} else {
			if (call.params == undefined)
				call.fnc.call(call.target, _callback);
			else
				call.fnc.call(call.target, call.params, _callback);
		}
	}
	return this;
}

Concurrent.prototype.abort = function () {
	this._abort = true;
	if (this._abortHandler !== null)
		this._abortHandler.apply(null, arguments);
	return this;
}

module.exports = Concurrent;
