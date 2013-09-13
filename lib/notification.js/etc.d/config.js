var env = require('../environemnt');

(env).
	/** Performance Parameters **/
	set('MySQLConnectionLimitKey', 64).
	set('SQSReceiveMessageWaitTimeSeconds', 10). // [0, 20]
	// keep this low so throttling can be more articulate -> better load balancing
	set('SQSMaxNumberOfMessages', 1). // [1, 10]
	set('ProcessMaxLagMilliseconds', 100).
	set('JobInternalBatchingSize', 4). // [1, 24]
	set('MaintenanceJobInternalBatchSize', 10). // [1, 24]
	set('TimelineJobBatchSize', 10). // [1, 24]
	set('UpdateJobBatchSize', 10).
	/** Windowing Parameters  **/
	set('TimelineWorkerEvery___Minutes', 1). // [0, 59]
	set('MaintenanceWorkerHour', 9). // [1, 23]
	set('PreparationWindowInfimumDt_sec', -3600).
	set('PreparationWindowSupremumDt_sec', 172800).
	set('DeleteWindowInfimumDt_sec', -86400)
;
