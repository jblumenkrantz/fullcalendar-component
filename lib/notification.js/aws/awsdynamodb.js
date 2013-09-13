
var AWSDynamoTable = function (dynamodb, tableName) {
	this.dynamodb = dynamodb;
	this.tableName = tableName;
}

AWSDynamoTable.prototype._call = function (target, fnc, params, callback) {
	params = params || {};
	callback = callback || null;
	params.TableName = this.tableName;
	return fnc.call(target, params, callback);
}

AWSDynamoTable.prototype.deleteItem = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.deleteItem, params, callback);
}

AWSDynamoTable.prototype.describe = function (callback) {
	return this._call(this.dynamodb, this.dynamodb.describeTable, null, callback);
}

AWSDynamoTable.prototype.getItem = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.getItem, params, callback);
}

AWSDynamoTable.prototype.putItem = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.putItem, params, callback);
}

AWSDynamoTable.prototype.syncCondPutItem = function (
	item, hashKeyName, rangeKeyName, syncPrimNames, shouldPutCallback, callback
) {
	if (typeof callback !== 'function')
		throw "AWSDynamoTable.prototype.syncCondPutItem requires 'function' callback";
	var _this = this;
	var params = {Item:item, Expected: {}};
	params.Expected[syncPrimNames[0]] = {Exists:false};
	(this.putItem(params, null)).
		on('success', function(response) {
			callback(null, response.data);
		}).
		on('error', function(err) {
			if (err.code === 'ConditionalCheckFailedException') {
				(_this.getItem({
					Key: {HashKeyElement:item[hashKeyName], RangeKeyElement:item[rangeKeyName]},
					AttributesToGet:syncPrimNames,
					ConsistentRead:true
				},null)).
					on('success', function(response) {
						var shouldPut = shouldPutCallback(item, response.data.Item);
						if (typeof shouldPut === 'object') {
							// item is more recent than stored item
							_this.syncPutItem(
								shouldPut,
								hashKeyName,
								rangeKeyName,
								syncPrimNames,
								shouldPutCallback,
								callback, 
								response.data.ConsumedCapacityUnits
							);
						} else
							callback(null, response.data);
					}).
					on('error', function(err) {
						callback(err, null);
					}).
				send();
			} else
				callback(err, null);
		}).
	send();
}

AWSDynamoTable.prototype.syncPutItem = function (
	params, hashKeyName, rangeKeyName, syncPrimNames, shouldPutCallback, callback,
	consumedCapacityUnits
) {
	if (typeof callback !== 'function')
		throw "AWSDynamoTable.prototype.syncPutItem requires 'function' callback";
	consumedCapacityUnits = consumedCapacityUnits || 0;
	var _this = this;
	(this.putItem(params, null)).
		on('success', function(response) {
			response.data.ConsumedCapacityUnits += consumedCapacityUnits;
			callback(null, response.data);
		}).
		on('error', function(err) {
			if (err.code === 'ConditionalCheckFailedException') {
				(_this.getItem({
					Key: {
						HashKeyElement:params.Item[hashKeyName],
						RangeKeyElement:params.Item[rangeKeyName]
					},
					AttributesToGet:syncPrimNames,
					ConsistentRead:true
				},null)).
					on('success', function(response) {
						var shouldPut = shouldPutCallback(params.Item, response.data.Item);
						if (typeof shouldPut === 'object') {
							_this.syncPutItem(
								shouldPut,
								hashKeyName,
								rangeKeyName,
								syncPrimNames,
								shouldPutCallback,
								callback, 
								response.data.ConsumedCapacityUnits + response.data.ConsumedCapacityUnits
							);
						} else {
							response.data.ConsumedCapacityUnits += consumedCapacityUnits;
							callback(null, response.data);
						}
					}).
					on('error', function(err) {
						callback(err, null);
					}).
				send();
			} else
				callback(err, null);
		}).
	send();
}

AWSDynamoTable.prototype.query = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.query, params, callback);
}

AWSDynamoTable.prototype.scan = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.scan, params, callback);
}

AWSDynamoTable.prototype.updateItem = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.updateItem, params, callback);
}

AWSDynamoTable.prototype.update = function (params, callback) {
	return this._call(this.dynamodb, this.dynamodb.updateTable, params, callback);
}

AWSDynamoTable.batchGetItem = function (params, callback, dynamodb) {
	var request = null;
	if (params.length !== undefined && params.length > 0) {
		var requestItems = {};
		dynamodb = dynamodb || null;
		for (var i in params) {
			var param = params[i];
			if (param.table !== undefined && param.params !== undefined) {
				requestItems[param.table.tableName] = param.params;
				if (dynamodb === null)
					dynamodb = param.table.dynamodb;
			}
		}
		if (dynamodb !== null) {
			callback = callback || null;
			request = dynamodb.batchGetItem({RequestItems:requestItems}, callback);
		}
	}
	return request;
}

AWSDynamoTable.batchWriteItem = function (params, callback, dynamodb) {
	var request = null;
	if (params.length !== undefined && params.length > 0) {
		var requestItems = {};
		dynamodb = dynamodb || null;
		for (var i in params) {
			var param = params[i];
			if (param.table !== undefined && param.requests !== undefined) {
				requestItems[param.table.tableName] = param.requests;
				if (dynamodb === null)
					dynamodb = param.table.dynamodb;
			}
		}
		if (dynamodb !== null) {
			callback = callback || null;
			request = dynamodb.batchWriteItem({RequestItems:requestItems}, callback);
		}
	}
	return request;
}

AWSDynamoTable._cache = [];
AWSDynamoTable.set = function (name, queue) {
	AWSDynamoTable._cache[name] = queue;
}
AWSDynamoTable.get = function (name) {
	return AWSDynamoTable._cache[name];
}

module.exports = AWSDynamoTable;
