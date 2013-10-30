'use strict';

angular.module('pinwheelApp')
	.controller('EventCtl', function ($scope, Event, QuickAdd, $filter, ReminderService) {
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//default event object
		$scope.defaultEvent = function() {
			return {
				event_start: new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a")), //get date object set to next hour
				event_end: new Date($filter('date')((new Date()).addHours(2), "M/d/yyyy h:00 a")), //get date object set to next hour + 1
				all_day: "0",
				has_reminder: false,
				isRepeating: false
			};
		}

		$scope.formEvent = new Event($scope.defaultEvent());
		$scope.useReminderType = 'relative';
		
		//open form for adding of new event
		$scope.add = function() {
			$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
			ReminderService.checkCalendarReminder($scope.formEvent, $scope.calendarWatchers);
			$scope.addingEvent = true;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		//open form for editing of existing event
		$scope.edit = function(event) {
			$scope.event = event;	//store the original event object
			$scope.formEvent = new Event();
			angular.copy(event, $scope.formEvent);
			$scope.useReminderType = ($scope.formEvent.all_day=='1') ? 'absolute' : 'relative';
			(!$scope.formEvent.has_reminder && $scope.checkCalendarReminder());
			$scope.editingEvent = true;
			$scope.addingEvent = false;
			$scope.quickAdding = false;
		}

		//update existing event
		$scope.update = function() {
			angular.copy($scope.formEvent, $scope.event);
			$scope.event.$update({id: $scope.event.event_id}, function(event) {
				$scope.event = event;
				$scope.cancel();
			});
		}

		//save new event
		$scope.save = function(continuing) {
			$scope.formEvent.$save({}, function(newEvent) {
				$scope.events.push(newEvent);
				$scope.cancel(continuing);
				(continuing && angular.copy(newEvent, $scope.formEvent));
			});
		}

		//delete existing event
		$scope.delete = function() {
			$scope.event.$delete({id:$scope.event.event_id, version:$scope.event.version});
			$scope.cancel();
		}

		//cancel all event forms, reset event object unless continuing
		$scope.cancel = function(continuing) {
			if (continuing) {
				$scope.formEvent = new Event();
			}
			else {
				$scope.reset();
			}
			$scope.addingEvent = continuing;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		$scope.reset = function() {
			$scope.useReminderType = 'relative';
			$scope.formEvent = new Event($scope.defaultEvent());
			$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
			ReminderService.checkCalendarReminder($scope.formEvent, $scope.calendarWatchers);
		}

		$scope.usingOwnReminder = function() {
			ReminderService.usingOwnReminder($scope.formEvent);
		}

		$scope.noReminder = function() {
			ReminderService.noReminder($scope.formEvent);
		}

		$scope.quickAdder = {alwaysAdvanced: true};
		//open quickadd form for new event
		$scope.quickAdd = function() {
			if ($scope.quickAdder.alwaysAdvanced) {
				$scope.add();
			}
			else {
				$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
				$scope.quickAdding = true;
				$scope.addingEvent = false;
				$scope.editingEvent = false;
			}
		}

		//save new quickadded event
		$scope.quickSave = function() {
			angular.copy(QuickAdd($scope.quickAdd.text), $scope.formEvent);
			$scope.save();
		}

		$scope.checkCalendarReminder = function() {
			ReminderService.checkCalendarReminder($scope.formEvent, $scope.calendarWatchers);
			($scope.formEvent.all_day=='1' && $scope.formEvent.using_calendar_reminder && $scope.convertToAbsoluteReminder());
		}

		$scope.convertToAbsoluteReminder = function() {
			var translated = ReminderService.relativeToAbsolute($scope.formEvent, $scope.formEvent.event_start);
			$scope.formEvent.reminder_time = translated.date;
			$scope.formEvent.offset = translated.offset;
			$scope.formEvent.reminder_type = translated.type;
		}

		$scope.reminderTypeFilter = function(reminderType) {
			return (reminderType.type==$scope.useReminderType || reminderType.type=='both');
		}

		$scope.reminderToggle = function() {
			//if adding reminder
			if ($scope.formEvent.has_reminder) {
				//if no existing reminder (calendar or other), use default reminder settings
				($scope.formEvent.reminder_pref_id == null && ReminderService.reminderDefaultsEvent($scope.formEvent, $scope.user));
			}
			//if removing reminder
			else {
				$scope.checkCalendarReminder();
			}
		}

		$scope.allDayToggle = function() {
			//update a new event's models
			(!$scope.formEvent.hasOwnProperty("version") && newEvents());		

			//filter reminder type drop down
			$scope.useReminderType = ($scope.formEvent.all_day=='1') ? 'absolute' : 'relative';

			//handle the switch between all day and normal event reminders
			//types 3 and 7 are common to both so do nothing for those types
			if ($scope.formEvent.reminder_type != 3 && $scope.formEvent.reminder_type != 7) {
				if ($scope.formEvent.all_day=='0' && $scope.formEvent.using_calendar_reminder) {
					$scope.checkCalendarReminder();
				}
				else if ($scope.formEvent.all_day=='1' && $scope.formEvent.has_reminder) {
					$scope.convertToAbsoluteReminder();
				}
				else {
					ReminderService.reminderDefaultsEvent($scope.formEvent, $scope.user);
				}
			}

			function newEvents() {
				//update start and end times
				var format = ($scope.formEvent.all_day=="1") ? "M/d/yyyy" : "M/d/yyyy h:00 a";
				var s = ($scope.formEvent.event_start) ? new Date($scope.formEvent.event_start) : new Date();
				var e = ($scope.formEvent.event_end) ? new Date($scope.formEvent.event_end) : new Date();
				s.setHours((new Date()).addHours(1).getHours());
				e.setHours((new Date()).addHours(2).getHours());
				$scope.formEvent.event_start = new Date($filter('date')(s, format)); //get date object set to current hour
				$scope.formEvent.event_end = new Date($filter('date')(e, format)); //get date object set to current hour + 1
			}
		}
  });