'use strict';

angular.module('pinwheelApp')
	.controller('EventCtl', function ($scope, Event, QuickAdd, $filter, ReminderService) {
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//default event object
		$scope.defaultEvent = function() {
			return {
				start: new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a")), //get date object set to next hour
				end: new Date($filter('date')((new Date()).addHours(2), "M/d/yyyy h:00 a")), //get date object set to next hour + 1
				allDay: "0",
				has_reminder: false,
				isRepeating: false,
				reminder_offset: 2
			};
		}

		$scope.formEvent = new Event($scope.defaultEvent());
		$scope.useReminderType = 'relative';
		
		//open form for adding of new event
		$scope.add = function(defaults) {
			$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
			if(defaults){
				angular.extend($scope.formEvent, defaults);
			}
			ReminderService.checkCalendarReminder($scope.formEvent, $scope.calendarWatchers);
			$scope.addingEvent = true;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		//open form for editing of existing event
		$scope.edit = function(event, dialog) {
			$scope.bak = event.source;
			delete event.source;
			$scope.event = event;	//store the original event object

			$scope.formEvent = new Event(event);
			$scope.useReminderType = ($scope.formEvent.allDay=='1') ? 'absolute' : 'relative';
			(!$scope.formEvent.has_reminder && $scope.checkCalendarReminder());

			$scope.editingEvent = (dialog == true || dialog == null)? true:false;
			$scope.addingEvent = false;
			$scope.quickAdding = false;
		}

		//update existing event
		$scope.update = function() {
			angular.copy($scope.formEvent, $scope.event);
			$scope.event.$update({id: $scope.event.id}, function(updateEvent) {
				$scope.event.source = $scope.bak;
				$scope.event.version = updateEvent.version
				$scope.pinwheel.fullCalendar('updateEvent', $scope.event);
				$scope.cancel();
			});
		}

		//save new event
		$scope.save = function(continuing) {
			var event = angular.copy($scope.formEvent);
			$scope.formEvent.$save({}, function(newEvent) {
				$.each($scope.calendars, function(i,cal){
					if(cal.calendar_id == event.calendar_id){
						cal.events.push(newEvent);
						$scope.pinwheel.fullCalendar('refetchEvents');
					}
				});
				$scope.cancel(continuing);
				(continuing && angular.copy(newEvent, $scope.formEvent));
			});
		}

		//delete existing event
		$scope.delete = function(event) {
			$scope.pinwheel.fullCalendar('removeEvents',event.id);
			event.$delete({id:event.id, version:event.version});
			$scope.cancel();
		}

		//cancel all event forms, reset event object unless continuing
		$scope.cancel = function(continuing) {
			if (continuing) {
				$scope.formEvent = new Event();
			}
			else {
				if($scope.hasOwnProperty('bak')){
					$scope.event.source = $scope.bak;
				}
				$scope.reset(true);
			}
			$scope.addingEvent = continuing;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		//pass true to reset to load a brand new event object
		//false if reloading existing event 
		$scope.reset = function(loadNewEvent) {
			if (loadNewEvent) {
				$scope.formEvent = new Event($scope.defaultEvent());
				$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
			}
			else {
				angular.copy($scope.event, $scope.formEvent);
			}

			$scope.useReminderType = ($scope.formEvent.allDay=='1') ? 'absolute' : 'relative';
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

		$scope.switchCalendar = function() {
			if (!$scope.formEvent.has_reminder || $scope.formEvent.using_calendar_reminder) {
				$scope.checkCalendarReminder();
			}
		}

		$scope.checkCalendarReminder = function() {
			ReminderService.checkCalendarReminder($scope.formEvent, $scope.calendarWatchers);
			($scope.formEvent.allDay=='1' && $scope.formEvent.using_calendar_reminder && $scope.convertToAbsoluteReminder());
		}

		$scope.convertToAbsoluteReminder = function() {
			var translated = ReminderService.relativeToAbsolute($scope.formEvent, $scope.formEvent.start);
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

		$scope.onDateSelected = function() {
			if ($scope.formEvent.reminder_type == 3) {
				$scope.formEvent.reminder_datetime = angular.copy($scope.formEvent.start);
			}
		}

		$scope.allDayToggle = function() {
			//update a new event's models
			(!$scope.formEvent.hasOwnProperty("version") && newEvents());		

			//filter reminder type drop down
			$scope.useReminderType = ($scope.formEvent.allDay=='1') ? 'absolute' : 'relative';

			//handle the switch between all day and normal event reminders
			//types 3 and 7 are common to both so do nothing for those types
			if ($scope.formEvent.reminder_type != 3 && $scope.formEvent.reminder_type != 7) {
				if ($scope.formEvent.allDay=='0' && $scope.formEvent.using_calendar_reminder) {
					$scope.checkCalendarReminder();
				}
				else if ($scope.formEvent.allDay=='1' && $scope.formEvent.has_reminder) {
					$scope.convertToAbsoluteReminder();
				}
				else {
					ReminderService.reminderDefaultsEvent($scope.formEvent, $scope.user);
				}
			}

			function newEvents() {
				//update start and end times
				var format = ($scope.formEvent.allDay=="1") ? "M/d/yyyy" : "M/d/yyyy h:00 a";
				var s = ($scope.formEvent.start) ? new Date($scope.formEvent.start) : new Date();
				var e = ($scope.formEvent.event_end) ? new Date($scope.formEvent.event_end) : new Date();
				s.setHours((new Date()).addHours(1).getHours());
				e.setHours((new Date()).addHours(2).getHours());
				$scope.formEvent.start = new Date($filter('date')(s, format)); //get date object set to current hour
				$scope.formEvent.event_end = new Date($filter('date')(e, format)); //get date object set to current hour + 1
			}
		}
		$scope.previous = function(){
			$scope.pinwheel.fullCalendar('prev');
   			$scope.routeDate = $scope.pinwheel.fullCalendar('getDate');
		}
		$scope.next = function(){
			$scope.pinwheel.fullCalendar('next');
			$scope.routeDate = $scope.pinwheel.fullCalendar('getDate');
		}
  });
