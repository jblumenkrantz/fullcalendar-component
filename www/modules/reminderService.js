'use strict';

angular.module('pinwheelApp').service('ReminderService', function($filter, DeviceService) {
	return {
		offsetMultiplier: [1,60,1440],
		setReminderProperties: function(resource) {
			resource.has_reminder = true;
			resource.reminder_type = parseInt(resource.reminder_type);
			if (resource.reminder_type < 3) {
				resource.reminder_offset = this.getOffsetFromMinsBefore(resource);
			}
			else if (resource.reminder_type > 3 && resource.reminder_type != 7) {
				resource.reminder_offset = (resource.reminder_type == 6) ? resource.mins_before : 2;
				resource.reminder_time = parseInt(resource.absolute_date)*1000;
			}
			else {
				resource.reminder_datetime = parseInt(resource.absolute_date)*1000;
			}
		},
		setReminderData: function(resource, type, start) {
			//these reminder types translate to minutes before (relative)
			resource.event_reminder_pref = (type=='task') ? 0 : 1; //should calendar.event_reminder_pref == 1?
			resource.reminder_aggregate = (type=='calendar') ? 1 : 0;
			if (resource.reminder_type == 0 ||
				resource.reminder_type == 1 ||
				resource.reminder_type == 2) {
					resource.mins_before = this.getMinutesBeforeFromOffset(resource);
					resource.absolute_date = 0;
					resource.relative = 1;
			}
			else if (resource.reminder_type == 7) {
				resource.absolute_date = 0;
				resource.mins_before = 0;
				resource.relative = 0;
			}	
			//all others translate to an absolute date
			else {
				resource.absolute_date = this.getAbsoluteDate(resource, start).getTime()/1000;
				resource.mins_before = (resource.reminder_type == 6) ? resource.reminder_offset : 0;
				resource.relative = 0;
			}
		},
		relativeToAbsolute: function(resource, start) {
			var mins = this.getMinutesBeforeFromOffset(resource);
			var obj = {
				date: new Date(start.getTime() - mins*60*1000),
				offset: mins/60,
				type: 5 
			}

			if (mins > 1440) {
			 	obj.type = 6;
			 	obj.offset = mins/1440;
			}

			return obj;
		},
		getAbsoluteDate: function(resource, start) {
			return (resource.reminder_type == 3) ?
				new Date(resource.reminder_datetime) :
				this.getAbsoluteDateFromSelect(resource, start);
		},
		getAbsoluteDateFromSelect: function(resource, start) {
			var date = angular.copy(start);
			var offset = 0;

			if (resource.reminder_type == 5) {
				offset = -1;
			}

			if (resource.reminder_type == 6) {
				offset = -resource.reminder_offset;
			}

			date.addDays(offset);
			date.setTimeByObj(resource.reminder_time);

			return date;
		},
		getOffsetFromMinsBefore: function(resource) {
			return resource.mins_before/this.offsetMultiplier[resource.reminder_type];
		},
		getMinutesBeforeFromOffset: function(resource) {
			return resource.reminder_offset*this.offsetMultiplier[resource.reminder_type];
		},
		checkCalendarReminder: function(resource, calendarWatchers) {
			angular.extend(resource, calendarWatchers[resource.calendar_id].reminder);
		},
		reminderDefaultsEvent: function(resource, user) {
			resource.reminder_type = (resource.allDay) ? 5 : 1;
			resource.reminder_offset = (resource.allDay) ? null : 2;
			resource.reminder_time = (resource.allDay) ? new Date("1970-01-01 "+user.settings.start_of_day) : null;
			resource.reminder_datetime = new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a"));
			resource.relative = (resource.allDay) ? 0 : 1;
			resource.absolute_date = (resource.allDay) ? null : 0;
		},
		reminderDefaultsTask: function(resource, user) {
			resource.reminder_type = (resource.has_due_date) ? 1 : 3;
			resource.reminder_offset = (resource.has_due_date) ? 2 : null;
			resource.reminder_datetime = (resource.has_due_date) ? null : new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a"));
		},
		noReminder: function(resource) {
			resource.reminder_type = 7;
			this.usingOwnReminder(resource);
		},
		usingOwnReminder: function(resource) {
			if (resource.using_calendar_reminder) {
				resource.using_calendar_reminder = false;
				resource.reminder_pref_id = null;
			}
		},
		getCalendarReminderProperties: function(calendar) {
			var props = {
				has_reminder: (calendar.reminder_pref_id!=null),
				using_calendar_reminder: undefined
			};

			if (calendar.reminder_pref_id!=null) {
				props.using_calendar_reminder = true;
				props.reminder_pref_id = calendar.reminder_pref_id;
				props.reminder_type = calendar.reminder_type;
				props.reminder_offset = calendar.reminder_offset;
			}

			return props;
		}
	}
});
