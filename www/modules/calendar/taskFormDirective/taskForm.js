'use strict';

angular.module('pinwheelApp')
	.directive('taskForm', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/taskFormDirective/_task_form.html',
			scope: {
				task: '=',
				calendars: '=',
				save: '&',
				cancel: '&',
				delete: '&',
				reminderToggle: '&',
				switchCalendar: '&',
				checkCalendarReminder: '&',
				dueDateToggle: '&',
				reminderTypeFilter: '&',
				reminderTypes: '=',
				usingOwnReminder: '&',
				noReminder: "&"
			}
		}
	});