#!/usr/bin/env node
console.log('Configure...');
var brcruntime = require('./brcruntime').load(main);

var dateFormat = require('./utils/dateformat');
var now = new Date();

function main() {
	console.log('Run...'+dateFormat(now,"m/d/yyyy HH:MM:ss Z"));
	var queue  = require('./queues/remmodqueue').sharedInstance();
	var ModificationWorker = require('./workers/modworker');
	var mod = new ModificationWorker(queue);
	mod.run();
}
