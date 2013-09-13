#!/usr/bin/env node
console.log('Configure...');
var brcruntime = require('../brcruntime').load(main);

function main() {
	console.log('Run...');
	pullModificationMessages();
}

function pullModificationMessages() {
	var queue  = require('../queues/remmodqueue').sharedInstance();
	var ReminderModificationMessage = require('../queues/remmodmsg');
	var request = queue.receiveModificationMessage({}, function (err, data) {
		if (data && data.length > 0) {
			console.dir(data);
			ReminderModificationMessage.delete(data, queue);
		}
		pullModificationMessages();
	});
}
