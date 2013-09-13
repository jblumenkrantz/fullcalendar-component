<?php

/* Deployment Type */
$IN_DEVELOPMENT = FALSE;
EVEnvironment:: setDeploymentType($IN_DEVELOPMENT? EVEnvironment:: $DEVELOPMENT: EVEnvironment:: $PRODUCTION);

/* AWS */
EVEnvironment:: set(EVAWSAuth:: $KeyName, 'AKIAJB23KHMJHZIRIVYA');
EVEnvironment:: set(EVAWSAuth:: $SecretName, '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');

/* Mysql */
EVEnvironment:: set(MySQLConnection:: $NamesKey, array(
	MySQLConnection:: $DefaultName => array(
		MySQLConnection:: $ServerKey   => 'brc.c5ecuyrmkbx8.us-east-1.rds.amazonaws.com',
		MySQLConnection:: $UsernameKey => 'sdb_admin',
		MySQLConnection:: $PasswordKey => 're4mation',
		MySQLConnection:: $DatabaseKey => 'pinwheel',
		MySQLConnection:: $PortKey => 6607 // Mandatory
	)
));
EVEnvironment:: set(DistributedMySQLConnection:: $ReadGroupKey, array(
	MySQLConnection:: $DefaultName,
	// 'TEST_JACOB'
));
EVEnvironment:: set(DistributedMySQLConnection:: $WriteGroupKey, array(
	MySQLConnection:: $DefaultName,
	// 'TEST_JACOB'
));

/* Mssql */
EVEnvironment:: set(MsSQLConnection:: $NamesKey, array(
	'Zone' => array(
		MsSQLConnection:: $ServerKey   => '10.0.0.9:1433',
		MsSQLConnection:: $UsernameKey => 'WebAppUser',
		MsSQLConnection:: $PasswordKey => 'pc4t6',
		MsSQLConnection:: $DatabaseKey => 'Zone'
	), 
	'SDIMain' => array(
		MsSQLConnection:: $ServerKey   => '10.0.0.41:1433',
		MsSQLConnection:: $UsernameKey => 'WebAppUser',
		MsSQLConnection:: $PasswordKey => 'pc4t6',
		MsSQLConnection:: $DatabaseKey => 'SDIMain'
	)
));

/* BRC Event Modification Queue */
$cu = '_'.get_current_user();
EVEnvironment:: set(BRCReminderModificationQueue:: $DefaultQueueNameKey,
	$IN_DEVELOPMENT?
		'com_schooldatebooks_brc_BRCReminderModificationQueue'.$cu:
		'com_schooldatebooks_brc_BRCReminderModificationQueue'
);
EVEnvironment:: set(BRCReminderModificationQueue:: $AllowQueueAccessKey, $IN_DEVELOPMENT? TRUE: TRUE);

/* BRC Notification Maintenance Queue */
EVEnvironment:: set(BRCNotificationMaintenanceQueue:: $DefaultQueueNameKey,
	$IN_DEVELOPMENT?
		'com_schooldatebooks_brc_BRCNotificationMaintenanceQueue'.$cu:
		'com_schooldatebooks_brc_BRCNotificationMaintenanceQueue'
);

/* BRC Notification Timeline Queue */
EVEnvironment:: set(BRCNotificationTimelineQueue:: $DefaultQueueNameKey,
	$IN_DEVELOPMENT?
		'com_schooldatebooks_brc_BRCNotificationTimelineQueue'.$cu:
		'com_schooldatebooks_brc_BRCNotificationTimelineQueue'
);

/* Crypto */
EVEnvironment:: set(Authorize:: $SkipURLKeyNameKey, $IN_DEVELOPMENT? 'dbg_skipauth': FALSE);
EVEnvironment:: set(Authorize:: $SigningKeyKey, "c54e858c66ccb34fe2b1e188b9ead28c094f3a55");
EVEnvironment:: set(Authorize:: $PasswordSaltKey, "cf522aa095e3431d71ce3d04d14f3e4792661386");
EVEnvironment:: set(Authorize:: $TokenTTLKey, 3600);


/* DynamoDB Table Names */
$timelineName = 'com_schooldatebooks_brc_NotificationTimeline'.($IN_DEVELOPMENT?$cu:'');
$preparedName = 'com_schooldatebooks_brc_NotificationPrepared'.($IN_DEVELOPMENT?$cu:'');;
$preferencesName = 'com_schooldatebooks_brc_NotificationPreferences'.($IN_DEVELOPMENT?$cu:'');
$maintenanceName = 'com_schooldatebooks_brc_NotificationMaintenance'.($IN_DEVELOPMENT?$cu:'');
EVEnvironment:: set(BRCDynamoNamespace:: $NotificationTimeline, $timelineName);
EVEnvironment:: set(BRCDynamoNamespace:: $NotificationPrepared, $preparedName);
EVEnvironment:: set(BRCDynamoNamespace:: $NotificationPreferences, $preferencesName);
EVEnvironment:: set(BRCDynamoNamespace:: $NotificationMaintenance, $maintenanceName);

