var env = require('../environemnt');

function garbageCollectionAttrNames () {
	return ['isDeleted', 'deletedAtTS'];
}

function garbageCollectionItem (item, isDeleted, deletedAtTS) {
	isDeleted = isDeleted !== undefined? isDeleted = +isDeleted + '': '0';
	deletedAtTS = deletedAtTS !== undefined? deletedAtTS + '': '0';
	var names = garbageCollectionAttrNames();
	item[names[0]] = {N:isDeleted};
	item[names[1]] = {N:deletedAtTS};
	return item;
}

function garbageCollectionDeletedItem (item) {
	return garbageCollectionItem(item, true, parseInt(Date.now() / 1000));
}

function garbageCollectionItemIsMarkedDeleted (item) {
	return item[garbageCollectionAttrNames()[0]].N == true;
}

function vectorClockAttrName () {
	return 'vclock';
}

function vectorClockItem (item, vector) {
	var vclock = '';
	for (var i in vector) {
		var clock = vector[i];
		vclock += clock.last_modified +','+ clock.version +':';
	}
	item[vectorClockAttrName()] = {S:vclock};
	return item;
}

function vectorClockFromItem (item) {
	var vclock = item[vectorClockAttrName()].S.split(':');
	var vector = [];
	for (var i in vclock) {
		var clock = vclock[i].split(',');
		if (clock.length == 2)
			vector.push({
				last_modified:clock[0],
				version:clock[1]
			});
	}
	return vector;
}

function cmpVectorClockPrime (v1, v2, primeIndex) {
	return v1[primeIndex].version - v2[primeIndex].version;
}

function cmpVectorClock (v1, v2, primeIndex) {
	var dp = v1[primeIndex].version - v2[primeIndex].version;
	if (dp === 0) {
		for (var i in v1) {
			// left to right compare
			if (i === primeIndex) continue;
			dp = v1[i].version - v2[i].version;
			if (dp !== 0) break;
		}
	}
	return dp;
}

function reminderPreferenceItem (
	item, isAggregate, isEventType, isRelative, relativeSec, absoluteTS, userId
) {
	item.isAggregate = {N:+isAggregate + ''};
	item.isEventType = {N:+isEventType + ''};
	item.isRelative = {N:+isRelative + ''};
	if (isRelative == true) item.relativeSec = {N:relativeSec + ''};
	else item.absoluteTS = {N:absoluteTS + ''};
	item.userId = {S:userId};
	return item;
}

function reminderPreferenceItemWithRPref (item, rpref) {
	return reminderPreferenceItem(item,
		rpref.aggregate == true,
		rpref.event_reminder_pref == true,
		rpref.relative == true,
		reminderPreferenceRelativeTSWithPref(rpref),
		rpref.absolute_date,
		rpref.user_id
	);
}

function reminderPreferenceIsEventType (item) {
	return item.isEventType.N == true;
}

function reminderPreferenceIsRelativeItem (item) {
	return item.isRelative.N == true;
}

function reminderPreferenceAbsoluteTS (item) {
	return item.absoluteTS.N;
}

function reminderPreferenceRelativeSec (item) {
	return item.relativeSec.N;
}

function rprefFromReminderPreferenceItem (id, rangeId, last_modified, version, item) {
	var rpref = {};
	rpref.reminder_pref_id = rangeId;
	var aggregate = id.substring(0,9) === 'calendar_';
	rpref.calendar_id = aggregate? id: 0;
	rpref.event_id = !aggregate && id.substring(0,6) === 'event_'? id: 0;
	rpref.task_id = !aggregate && rpref.event_id === 0? id: 0;
	rpref.event_reminder_pref = rpref.event_id !== 0;
	rpref.user_id = item.userId.S;
	rpref.mins_before = item.isRelative.N == true
		? mins_beforeFromReminderPreferenceItem(item)
		: 0;
	rpref.absolute_date = item.isRelative.N == false? item.absoluteTS.N: 0;
	rpref.relative = item.isRelative.N == true;
	rpref.aggregate = aggregate;
	rpref.last_modified = last_modified;
	rpref.version = version;
	return rpref;
}

function reminderPreferenceRelativeTSWithPref (rpref) {
	return rpref.mins_before*(-60);
}

function mins_beforeFromReminderPreferenceItem (item) {
	return item.relativeSec.N / (-60);
}

function preparedTimeWindowTS () {
	var nowTS = Date.now() / 1000;
	return {
		infimum:nowTS + env.get('PreparationWindowInfimumDt_sec'),
		supremum:nowTS + env.get('PreparationWindowSupremumDt_sec')
	};
}

function timeInTimeWindowTS (tw, ts) {
	return tw.infimum <= ts <= tw.supremum;
}

function notificationTime (assetTimeTS, rpref) {
	return rpref.relative == true
		? parseInt(assetTimeTS)+parseInt(reminderPreferenceRelativeTSWithPref(rpref))
		: rpref.absolute_date;
}

function notificationTimeWithPreferenceItem(assetTimeTS, item) {
	return item.isRelative.N == true
		? parseInt(assetTimeTS)+parseInt(item.relativeSec.N)
		: item.absoluteTS.N;
}


module.exports.vectorClockAttrName = vectorClockAttrName;
module.exports.garbageCollectionItemIsMarkedDeleted = garbageCollectionItemIsMarkedDeleted;
module.exports.garbageCollectionDeletedItem = garbageCollectionDeletedItem;
module.exports.garbageCollectionItem = garbageCollectionItem;
module.exports.garbageCollectionAttrNames = garbageCollectionAttrNames;
module.exports.reminderPreferenceItem = reminderPreferenceItem;
module.exports.cmpVectorClock = cmpVectorClock;
module.exports.cmpVectorClockPrime = cmpVectorClockPrime;
module.exports.vectorClockFromItem = vectorClockFromItem;
module.exports.vectorClockItem = vectorClockItem;
module.exports.notificationTimeWithPreferenceItem = notificationTimeWithPreferenceItem;
module.exports.notificationTime = notificationTime;
module.exports.timeInTimeWindowTS = timeInTimeWindowTS;
module.exports.preparedTimeWindowTS = preparedTimeWindowTS;
module.exports.reminderPreferenceRelativeTSWithPref = reminderPreferenceRelativeTSWithPref;
module.exports.reminderPreferenceItemWithRPref = reminderPreferenceItemWithRPref;
module.exports.rprefFromReminderPreferenceItem = rprefFromReminderPreferenceItem;
module.exports.reminderPreferenceIsRelativeItem = reminderPreferenceIsRelativeItem;
module.exports.reminderPreferenceIsEventType = reminderPreferenceIsEventType;
module.exports.reminderPreferenceAbsoluteTS = reminderPreferenceAbsoluteTS;
module.exports.reminderPreferenceRelativeSec = reminderPreferenceRelativeSec;
