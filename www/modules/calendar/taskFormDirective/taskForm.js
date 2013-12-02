'use strict';

angular.module('pinwheelApp')
	.directive('taskForm', function(){
		return {
			restrict: 'E',
			replace: true,
			templateUrl: 'modules/calendar/taskFormDirective/_task_form.html',
			scope: {
				task: '=',
				calendars: '=',
				reminderTypes: '=',
				reminderTypeFilter: '&',
				save: '&',
				cancel: '&',
				reset: '&',
				delete: '&',
				reminderToggle: '&',
				switchCalendar: '&',
				checkCalendarReminder: '&',
				dueDateToggle: '&',
				usingOwnReminder: '&',
				noReminder: "&"
			}
		}
	});