
var JobStream = function (batchSize, streamHandler, abortHandler) {
	this._batchSize = batchSize;
	this._streamHandler = _normalizedCallback(streamHandler);
	this._endHandler = null;
	this._outstandingCount = 1;
	this._buffer = [];
	this._abortHandler = null;
	if (abortHandler !== undefined)
		this._abortHandler = abortHandler;
	this._abort = false;
}

function _normalizedCallback (callback) {
	var norm = callback;
	if (typeof callback === 'object') {
		if (callback.target === undefined)
			norm = callback.fnc;
		else
			norm = function () {
				return callback.fnc.apply(callback.target, arguments);
			}
	}
	return norm;
}

function _oshifta (o) {
	delete o['0'];
	var args_shift = [];
	for (var i in o) args_shift.push(o[i]);
	return args_shift;
}

JobStream.prototype.stream = function () {
	if(this._abort) return this;
	if (this._buffer.length < this._batchSize - 1) {
		this._buffer.push(arguments);
	}
	else {
		this._buffer.push(arguments);
		var _this = this;
		this._outstandingCount++;
		this._streamHandler.call(null, this._buffer, function (continueStreaming) {
			if (!continueStreaming) {
				_this.abort(_oshifta(arguments));
			}
			else if (
				!_this._abort &&
				--_this._outstandingCount === 0 &&
				typeof _this._endHandler === 'function'
			)
				_this._endHandler.apply(null, _oshifta(arguments));
		});
		this._buffer = [];
	}
	return this;
}

JobStream.prototype.cleanup = function () {
	this._streamHandler = null;
	this._endHandler = null;
	this._buffer = null;
	this._abortHandler = null;
}

JobStream.prototype.end = function (handler) {
	if(this._abort) return this;
	var _this = this;
	var userEndHandler = _normalizedCallback(handler);
	this._endHandler = function () {
		if (!_this._abort) {
			if (_this._buffer.length !== 0) {
				_this._streamHandler.call(null, _this._buffer, function (continueStreaming) {
					if (!continueStreaming)
						_this.abort(_oshifta(arguments));
					else if (!_this._abort && typeof userEndHandler === 'function')
						userEndHandler.apply(null, _oshifta(arguments));
					_this.cleanup();
					_this = null;
				});
				if (_this !== null) _this._buffer = [];
			} else if (typeof userEndHandler === 'function') {
				userEndHandler.apply(null, arguments);
				_this.cleanup();
				_this = null;
			}
		} else {
			_this.cleanup();
			_this = null;
		}
	}

	if (--this._outstandingCount === 0)
		this._endHandler.apply(null, _oshifta(arguments));
	return this;
}

JobStream.prototype.abort = function () {
	this._abort = true;
	if (this._abortHandler !== null) {
		this._abortHandler.apply(null, arguments);
		this.cleanup();
	}
	return this;
}

module.exports = JobStream;
