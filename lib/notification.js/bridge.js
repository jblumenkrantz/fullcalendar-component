var execFile = require('child_process').execFile;
var env = require('./environemnt');

var Bridge = function() {
	this._loaded = false;
	this._loading = false;
	this._loadingCallback = [];
	this._bridged = null;
}
Bridge.prototype = {};
Bridge.prototype.load = function (cpath, callback) {
	if (!this._loaded) {
		if (!this._loading) {
			var _this = this;
			this._loading = true;
			execFile(cpath, null, null, function (error, stdout, stderr){
				// set states
				_this._bridged = JSON.parse(stdout);
				_this._loaded = true;
				_this._loading = false;
				// callbacks
				callback(_this);
				var loadingCallback = _this._loadingCallback;
				if (loadingCallback.length > 0) {
					for (var i in loadingCallback) {
						callback = loadingCallback[i];
						callback(_this);
					}
					this._loadingCallback = [];
				}
			});
		} else
			// interim callback storage
			this._loadingCallback.push(callback);
	} else
		this.callback();
		
	return this;
}

Bridge.prototype.get = function (key) {
	var value = null, bridged = this._bridged;
	if (bridged && bridged[key] !== undefined)
		value = bridged[key];
	else
		value = env.get(key);
	return value;
}

Bridge.prototype.keys = {
	/** MySQL Default **/
	MySQL_Names          : 'MySQLConnection::NamesKey',
	MySQL_DefaultName    : 'MySQLConnection::DefaultName',
	MySQL_Server         : 'MySQLConnection::ServerKey',
	MySQL_Username       : 'MySQLConnection::UsernameKey',
	MySQL_Password       : 'MySQLConnection::PasswordKey',
	MySQL_Database       : 'MySQLConnection::DatabaseKey',
	MySQL_Port           : 'MySQLConnection::PortKey',
	DMySQL_ReadGroupKey  : 'DistributedMySQLConnection::ReadGroupKey',
	DMySQL_WriteGroupKey : 'DistributedMySQLConnection::WriteGroupKey',
	/** AWS Credentials **/
	AWSKey    : 'AWSKey',
	AWSSecret : 'AWSSecret',
	/** Deployement Environment **/
	DeploymentType : 'EVEnvironment::_deplomentTypeKey',
	DeploymenDevel : 'EVEnvironment::DEVELOPMENT',
	DeploymenProd  : 'EVEnvironment::PRODUCTION',
	/** Reminder Modification Queue Default **/
	ModificationQueueName   : 'BRCReminderModificationQueue::DefaultQueueNameKey',
	ModificationAllowAccess : 'BRCReminderModificationQueue::AllowQueueAccessKey',
	/** Notification Maintenance Queue Default **/
	MaintenanceQueueName : 'BRCNotificationMaintenanceQueue::DefaultQueueNameKey',
	/** Notification Timeline Queue Default **/
	TimelineQueueName : 'BRCNotificationTimelineQueue::DefaultQueueNameKey',
	/** Reminder Modification Queue Serialization Object Types **/
	// Task Modificaitons
	TaskModificationType          : 'TaskModificationType',
	TaskModificationCreatedAction : 'TaskModificationCreatedAction',
	TaskModificationUpdatedAction : 'TaskModificationUpdatedAction',
	TaskModificationDeletedAction : 'TaskModificationDeletedAction',
	// Event Modifications
	EventModificationType          : 'EventModificationType',
	EventModificationCreatedAction : 'EventModificationCreatedAction',
	EventModificationUpdatedAction : 'EventModificationUpdatedAction',
	EventModificationDeletedAction : 'EventModificationDeletedAction',
	// Reminder Modifications
	ReminderPrefModificationType          : 'ReminderPrefModificationType',
	ReminderPrefModificationCreatedAction : 'ReminderPrefModificationCreatedAction',
	ReminderPrefModificationUpdatedAction : 'ReminderPrefModificationUpdatedAction',
	ReminderPrefModificationDeletedAction : 'ReminderPrefModificationDeletedAction',
	// DynamoDB Table Names
	NotificationTimeline         : 'BRCDynamoNamespace::NotificationTimeline',
	NotificationPrepared         : 'BRCDynamoNamespace::NotificationPrepared',
	NotificationPreferenceIndex  : 'BRCDynamoNamespace::NotificationPreferenceIndex',
	NotificationPreferences      : 'BRCDynamoNamespace::NotificationPreferences',
	NotificationMaintenance      : 'BRCDynamoNamespace:$NotificationMaintenance'
};

Bridge.prototype.inDevelopment = function () {
	return this.get(this.keys.DeploymentType) == this.keys.DeploymenDevel;
}

Bridge.prototype.inProduction = function () {
	return this.get(this.keys.DeploymentType) == this.keys.DeploymenProd;
}

Bridge.prototype.AWSCredentials = function () {
	return {
		accessKeyId     : this.get(this.keys.AWSKey),
		secretAccessKey : this.get(this.keys.AWSSecret)
	};
}

Bridge.prototype.modificationQueueName = function () {
	return this.get(this.keys.ModificationQueueName);
}

Bridge.prototype.maintenanceQueueName = function () {
	return this.get(this.keys.MaintenanceQueueName);
}

Bridge.prototype.timelineQueueName = function () {
	return this.get(this.keys.TimelineQueueName);
}

Bridge.prototype.modificationAllowAccess = function () {
	return this.get(this.keys.ModificationAllowAccess);
}

Bridge.sharedInstance = null;
Bridge.getSharedInstance = function () {
	if (this.sharedInstance === null) {
		this.sharedInstance = new Bridge();
	}
	return this.sharedInstance;
}
module.exports = Bridge.getSharedInstance();

