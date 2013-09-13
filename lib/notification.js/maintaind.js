#!/usr/bin/env node
console.log('Configure...');
var brcruntime = require('./brcruntime').load(main);

var dateFormat = require('./utils/dateformat');
var now = new Date();

function main() {
	console.log('Run...'+dateFormat(now,"m/d/yyyy HH:MM:ss Z"));
	var queue = require('./queues/maintenancequeue').sharedInstance();
	var MaintenanceWorker = require('./workers/maintenanceworker');
	var maintenanceworker = new MaintenanceWorker(queue);
	maintenanceworker.testTimerInASecond = process.argv.length > 2 && process.argv[2] === '-D';
	maintenanceworker.run();
}