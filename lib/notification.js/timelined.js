#!/usr/bin/env node
console.log('Configure...');
var brcruntime = require('./brcruntime').load(main);

var dateFormat = require('./utils/dateformat');
var now = new Date();

function main() {
	console.log('Run...'+dateFormat(now,"m/d/yyyy HH:MM:ss Z"));
	var queue = require('./queues/timelinequeue').sharedInstance();
	var TimelineWorker = require('./workers/timelineworker');
	var timelineworker = new TimelineWorker(queue);
	timelineworker.testTimerInASecond = process.argv.length > 2 && process.argv[2] === '-D';
	timelineworker.run();
}