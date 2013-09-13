
var Serialize = function () {
	this._calls = [];
	this._callbacks = [];
}
Serialize.automatic = 'automatic';

Serialize.prototype.thenIf = function (condition, call, callback) {
	if (condition)
		return this.then (call, callback);
	return this;
}

Serialize.prototype.then = function (call, callback) {
	var type = typeof call;
	if (
		type === 'function' ||
		(type === 'object' && call.fnc !== undefined)
	) {
		this._calls.push(call);
		this._callbacks.push(callback);
	}
	return this;
}

Serialize.prototype._execute = function () {
	//console.log(JSON.stringify(this._calls));
	if (this._calls.length < 1) return this;
	var call = this._calls.pop();
	var callback = this._callbacks.pop();
	var type = typeof call;
	var _this = this;
	var _callback = function () {
		var shouldContinue = true;
		if (typeof callback === 'function') {
			var ret = callback.apply(null, arguments);
			if (ret !== undefined)
				shouldContinue = ret;
		}
		if (shouldContinue)
			_this._execute();
	}

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
	if (callback === Serialize.automatic)
		_callback.call(null);
	return this;
}

Serialize.prototype.execute = function () {
	this._calls.reverse();
	this._callbacks.reverse();
	return this._execute();
}

module.exports = Serialize;

(function text(){
	(new Serialize).then(
		function (callback) {console.log(1); callback('hello');},
		function (name) {console.log('2:'+name);}
	).then(
		{fnc: function (callback) {console.log(3); callback();}},
		function () {console.log(4);}
	).then(
		{
			params: {n:6},
			fnc: function (params, callback) {console.log(5); callback(params.n);}
		},
		function (n) {console.log(n);}
	).then(
		{
			target: {n:7},
			fnc: function (callback) {console.log(this.n); callback();}
		},
		function () {console.log(8);}
	).then(
		{
			target: {n:9},
			params: {n:10},
			fnc: function (params, callback) {console.log(this.n); callback(params.n);}
		},
		function (n) {console.log(n);}
	).execute();
});

