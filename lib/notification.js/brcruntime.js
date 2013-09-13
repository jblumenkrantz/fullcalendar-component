var Serialize = require('./utils/serialize');
var env    = require('./environemnt');
var bridge = require('./bridge');

var BRCRuntime = function () {
	this.bridge = env;
	this.runtime = bridge;
	this._loadWasCalled = false;
	this._finishedLoading = false;
	this._callbacks = [];
}
BRCRuntime.EnvironemntConfigurePath = './etc.d/config.js';
BRCRuntime.BridgeConfigurePath = __dirname+'/etc.d/bridge';

BRCRuntime.critical = function (err) {
	console.dir(err);
	process.exit(1);
}

BRCRuntime.prototype.load = function (callback) {
	if (!this._loadWasCalled) {
		this._loadWasCalled = true;
		var _this = this;
		(new Serialize).
			then(
				{
					params:BRCRuntime.BridgeConfigurePath,
					target:bridge, fnc:bridge.load
				}
			).
			then(
				{
					params:BRCRuntime.EnvironemntConfigurePath,
					target:env, fnc:env.load
				}
			).
			then(function (callback) {
				_bridgeWMySQLW();
				_bridgeAWS();
				_bridgeRemmodQueue(callback);
			}).
			then(_bridgeMaintenanceQueue).
			then(_bridgeTimelineQueue).
			then(_bridgeDynamoTables).
			then(function () {
				if (callback !== undefined) callback();
				var callbacks = _this._callbacks;
				for (var i in callbacks)
					callbacks[i]();
				_this._finishedLoading = true;
			}).execute();
	} else if (callback !== undefined) {
		if (this._finishedLoading)
			callback();
		else
			this._callbacks.push(callback);
	}
	
	return this;
}

BRCRuntime._instance = null;
BRCRuntime.sharedInstance = function () {
	if (BRCRuntime._instance === null)
		BRCRuntime._instance = new BRCRuntime();
	return BRCRuntime._instance;
}
module.exports = BRCRuntime.sharedInstance();


/**
	Configuration
*/
function _bridgeWMySQLW () {
	var wmysqlw = require('./utils/wmysqlw.js');
	var keys = bridge.keys;
	var names = bridge.get(keys.MySQL_Names) || {};
	var readNames = bridge.get(keys.DMySQL_ReadGroupKey) || {};
	var readConfig = [];
	for (var i in readNames) {
		var name = readNames[i];
		if (names[name] !== undefined) {
			var config = names[name];
			readConfig.push(wmysqlw.makeConfiguration(
				config[keys.MySQL_Server],
				config[keys.MySQL_Username],
				config[keys.MySQL_Password],
				config[keys.MySQL_Database],
				config[keys.MySQL_Port]
			));
		}
	}
	var writeNames = bridge.get(keys.DMySQL_WriteGroupKey) || {};
	var writeConfig = [];
	for (var i in writeNames) {
		var name = writeNames[i];
		if (names[name] !== undefined) {
			var config = names[name];
			writeConfig.push(wmysqlw.makeConfiguration(
				config[keys.MySQL_Server],
				config[keys.MySQL_Username],
				config[keys.MySQL_Password],
				config[keys.MySQL_Database],
				config[keys.MySQL_Port]
			));
		}
	}
	wmysqlw.configureDefault(readConfig, writeConfig);
}

function _bridgeAWS() {
	var aws = require('aws-sdk');
	aws.config.update(bridge.AWSCredentials());
	aws.config.update({region: 'us-east-1'});
}

function _bridgeRemmodQueue (callback) {
	var aws = require('aws-sdk');
	var queue  = require('./queues/remmodqueue').sharedInstance();
	var sqs = (new aws.SQS()).client;
	sqs.createQueue({QueueName:bridge.modificationQueueName()}, function (err, data) {
		if (err)
			BRCRuntime.critical(err);
		else if (data && data.QueueUrl !== undefined) {
			queue.sqs = sqs;
			queue.queueUrl = data.QueueUrl;
		}
		var receiveMessageWaitTimeSeconds = env.get('SQSReceiveMessageWaitTimeSeconds') || 10;
		var request = sqs.setQueueAttributes({
			QueueUrl:data.QueueUrl,
			Attributes:{ReceiveMessageWaitTimeSeconds:receiveMessageWaitTimeSeconds}
		}, function (err, data){
			if (err)
				BRCRuntime.critical(err);
			else
				callback();
		});
	});
}

function _bridgeMaintenanceQueue (callback) {
	var aws = require('aws-sdk');
	var queue = require('./queues/maintenancequeue').sharedInstance();
	var sqs = (new aws.SQS()).client;
	sqs.createQueue({QueueName:bridge.maintenanceQueueName()}, function (err, data) {
		if (err)
			BRCRuntime.critical(err);
		if (data && data.QueueUrl !== undefined) {
			queue.sqs = sqs;
			queue.queueUrl = data.QueueUrl;
		}
		var receiveMessageWaitTimeSeconds = env.get('SQSReceiveMessageWaitTimeSeconds') || 10;
		var request = sqs.setQueueAttributes({
			QueueUrl:data.QueueUrl,
			Attributes:{ReceiveMessageWaitTimeSeconds:receiveMessageWaitTimeSeconds}
		}, function (err, data){
			if (err)
				BRCRuntime.critical(err);
			else
				callback();
		});
	});
}

function _bridgeTimelineQueue (callback) {
	var aws = require('aws-sdk');
	var queue = require('./queues/timelinequeue').sharedInstance();
	var sqs = (new aws.SQS()).client;
	sqs.createQueue({QueueName:bridge.timelineQueueName()}, function (err, data) {
		if (err)
			BRCRuntime.critical(err);
		if (data && data.QueueUrl !== undefined) {
			queue.sqs = sqs;
			queue.queueUrl = data.QueueUrl;
		}
		var receiveMessageWaitTimeSeconds = env.get('SQSReceiveMessageWaitTimeSeconds') || 10;
		var request = sqs.setQueueAttributes({
			QueueUrl:data.QueueUrl,
			Attributes:{ReceiveMessageWaitTimeSeconds:receiveMessageWaitTimeSeconds}
		}, function (err, data){
			if (err)
				BRCRuntime.critical(err);
			else
				callback();
		});
	});
}

function _tableIsActive (dynamo, tablename, callback) {
	dynamo.describeTable({TableName:tablename}, function (err, data) {
		if (err)
			BRCRuntime.critical(err);
		else {
			var status = data.Table.TableStatus;
			if (status == 'ACTIVE') {
				process.stdout.write('\n');
				callback();
			}
			else if (status == 'CREATING') {
				process.stdout.write('.');
				setTimeout(_tableIsActive, 1000, dynamo, tablename, callback);
			} else
				BRCRuntime.critical(tablename+' status was not ACTIVE | CREATING');
		}
	});
}

function _createTable (dynamo, idType, rangeType, tablename, callback) {
	dynamo.describeTable({TableName:tablename}, function (err, data) {
		if (err && err.statusCode === 400) {
			dynamo.createTable({
				TableName:tablename,
				KeySchema: {
					HashKeyElement:  {AttributeName:'id', AttributeType:idType},
					RangeKeyElement: {AttributeName:'rangeId', AttributeType:rangeType}
				},
				ProvisionedThroughput: {ReadCapacityUnits:10, WriteCapacityUnits:10}
			}, function (err, data) {
				if (err)
					BRCRuntime.critical(err);
				else {
					console.log('Creating '+tablename+'...');
					_tableIsActive (dynamo, tablename, callback);
				}
			});
		}
		else
			callback();
	});
}

function _bridgeDynamoTables (callback) {
	var aws = require('aws-sdk');
	var dynamo = (new aws.DynamoDB()).client;
	var AWSDynamoTable = require('./aws/awsdynamodb');
	(new Serialize).
		then(function (callback) {
			_createTable(dynamo, 'N', 'S', bridge.get(bridge.keys.NotificationTimeline), callback);
		}).
		then(function (callback) {
			_createTable(dynamo, 'S', 'S', bridge.get(bridge.keys.NotificationPrepared), callback);
		}).
		then(function (callback) {
			_createTable(dynamo, 'S', 'S', bridge.get(bridge.keys.NotificationPreferences), callback);
		}).
		then(function (callback) {
			_createTable(dynamo, 'S', 'S', bridge.get(bridge.keys.NotificationMaintenance), callback);
		}).
		then(function () {
			AWSDynamoTable.set ('TimelineTable',
				new AWSDynamoTable(dynamo, bridge.get(bridge.keys.NotificationTimeline))
			);
			AWSDynamoTable.set ('PreparedTable',
				new AWSDynamoTable(dynamo, bridge.get(bridge.keys.NotificationPrepared))
			);
			AWSDynamoTable.set ('PreferencesTable',
				new AWSDynamoTable(dynamo, bridge.get(bridge.keys.NotificationPreferences))
			);
			AWSDynamoTable.set ('Maintenance',
				new AWSDynamoTable(dynamo, bridge.get(bridge.keys.NotificationMaintenance))
			);
			callback();
		}).execute();
}

