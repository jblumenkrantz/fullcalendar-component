#!/usr/bin/env node
console.log('Configure...');
var brcruntime = require('../brcruntime').load(main);
var AWSDynamoTable = require('../aws/awsdynamodb');

function main() {
	console.log('Run...');
	if (process.argv.length < 3) {
		console.log('Please provide DynamoDB Table Name.');
		process.exit(1)
	}
	var aws = require('aws-sdk');
	var dynamo = (new aws.DynamoDB()).client;
	var tableName = process.argv.pop();
	var table = new AWSDynamoTable(dynamo, tableName);

	table.describe(function (err,data) {
		if (err) {
			console.log(err);
			process.exit(1);
		}
		clearTable(table, data.Table.KeySchema.HashKeyElement.AttributeName, data.Table.KeySchema.RangeKeyElement.AttributeName);
	});
}

function clearTable(table, idName, rangeName, exclusiveStartKey, items) {
	items = items || [];
	var params = {AttributesToGet:[idName, rangeName]};
	if (exclusiveStartKey !== undefined)
		params.ExclusiveStartKey = exclusiveStartKey;
	table.scan(params, function (err, data) {
		if (err) {
			console.log(err);
			process.exit(1);
		}
		if (data.Items !== undefined && data.Items.length > 0) {
			for (var i in data.Items)
				items.push(data.Items[i]);
		}
		if (data.LastEvaluatedKey !== undefined && data.LastEvaluatedKey !== null) {
			clearTable(table, idName, rangeName, data.LastEvaluatedKey, items);
		}
		else {
			console.log('Deleting ' + items.length + ' items...');
			batchDeleteItems(table, items);
		}
	});
}

function batchDeleteItems(table, items, unprocessedItems) {
	var requestSpace = 25;
	var requestsHash = {};
	var requests = [];

	if (unprocessedItems !== undefined) {
		if (unprocessedItems[table.tableName] !== undefined) {
			unprocessedItems = unprocessedItems[table.tableName];
			for (var i in unprocessedItems) {
				var key = unprocessedItems[i].DeleteRequest.Key;
				var hash = key.HashKeyElement.S + key.RangeKeyElement.S;
				requestsHash[hash] = 1;
				requests.push(unprocessedItems[i]);
			}
			requestSpace -= unprocessedItems.length;
		}
	}

	if (requestSpace > 0) {
		var rightItems = [];
		for (var i in items) {
			var item = items[i];
			var hash = item.id + item.rangeId;
			if (requestsHash[hash] === undefined) {
				if (requestSpace-- > 0) {
					var deleteItem = {Key: {HashKeyElement:item.id,RangeKeyElement:item.rangeId}};
					requests.push({DeleteRequest: deleteItem});
				} else
					rightItems.push(item);
			}
		}
		items = rightItems;
	}
	if (requests.length === 0) return;

	AWSDynamoTable.batchWriteItem([
		{table:table, requests:requests}
	], function(err, data) {
		if (err) {
			console.log(err);
			process.exit(1);
		}
		else {
			batchDeleteItems(table, items, data.UnprocessedItems);
		}
	});
}
