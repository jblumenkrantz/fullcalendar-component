'use strict';

angular.module('pinwheelApp')
	.controller('EventCtl', function ($scope, Event, QuickAdd, $filter) {
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//default event object
		$scope.defaultEvent = function() {
			return {
				event_start: new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a")), //get date object set to current hour
				event_end: new Date($filter('date')((new Date()).addHours(2), "M/d/yyyy h:00 a")), //get date object set to current hour + 1
				all_day: "0",
				has_reminder: false,
				isRepeating: false
			};
		}

		$scope.formEvent = new Event($scope.defaultEvent());
		
		//open form for adding of new event
		$scope.add = function() {
			$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
			$scope.checkCalendarReminder();
			$scope.addingEvent = true;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		//open form for editing of existing event
		$scope.edit = function(event) {
			console.log(event);
			$scope.event = event;	//store the original event object
			$scope.formEvent = new Event();
			angular.copy($scope.event, $scope.formEvent);
			$scope.checkCalendarReminder();
			$scope.editingEvent = true;
			$scope.addingEvent = false;
			$scope.quickAdding = false;
		}

		//update existing event
		$scope.update = function() {
			console.log($scope.formEvent);
			$scope.setReminderData();
			angular.copy($scope.formEvent, $scope.event);
			$scope.event.$update({id: $scope.event.event_id}, function(event) {
				angular.extend($scope.event, event);
				//$scope.event = event;
				$scope.cancel();
			});
		}

		//save new event
		$scope.save = function(continuing) {
			console.log($scope.formEvent);
			$scope.setReminderData();
			$scope.formEvent.$save({}, function(newEvent) {
				angular.extend(newEvent, $scope.formEvent);
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
			$scope.formEvent = new Event($scope.defaultEvent());
			$scope.formEvent.calendar_id = $scope.user.settings.default_calendar;
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

		$scope.$watch('formEvent.has_reminder', function(newVal, oldVal) {
			//prevent intial call
			if (newVal === oldVal) {
				return;
			}
			(newVal && $scope.formEvent.reminder_pref_id == null && $scope.reminderDefaults());
		});

		$scope.$watch('formEvent.all_day', function(newVal, oldVal) {
			//prevent intial call
			if (newVal === oldVal) {
				return;
			}
			console.log('formEvent.all_day $watch', newVal);

			(!$scope.formEvent.hasOwnProperty("version") && newEvents());		//update a new event's models
			($scope.formEvent.hasOwnProperty("version") && existingEvents());	//update an existing events models
			both(); 															//actions that apply to both

			function newEvents() {
				//update start and end times
				var format = (newVal=="1") ? "M/d/yyyy" : "M/d/yyyy h:00 a";
				var s = ($scope.formEvent.event_start) ? new Date($scope.formEvent.event_start) : new Date();
				var e = ($scope.formEvent.event_end) ? new Date($scope.formEvent.event_end) : new Date();
				s.setHours((new Date()).addHours(1).getHours());
				e.setHours((new Date()).addHours(2).getHours());
				$scope.formEvent.event_start = new Date($filter('date')(s, format)); //get date object set to current hour
				$scope.formEvent.event_end = new Date($filter('date')(e, format)); //get date object set to current hour + 1
			} 

			function existingEvents() {
				//nothing
			}

			function both() {
				if ($scope.formEvent.reminder_type != 3 && $scope.formEvent.reminder_type != 7) {
					if (newVal=='1' && $scope.formEvent.has_reminder) {
						//translate relavite to absolute
						var translated = $scope.relativeToAbsolute();
						$scope.formEvent.reminder_type = translated.type;
						$scope.formEvent.reminder_offset = translated.offset;
						$scope.formEvent.reminder_time = translated.date;
					}
					else if (newVal=='0' && $scope.formEvent.using_calendar_reminder) {
						$scope.checkCalendarReminder();
					}
					else {
						$scope.reminderDefaults();
					}
				}
			}
		});

		$scope.setReminderData = function() {
			//these reminder types translate to minutes before (relative)
			if ($scope.formEvent.reminder_type == 0 ||
				$scope.formEvent.reminder_type == 1 ||
				$scope.formEvent.reminder_type == 2) {
					$scope.formEvent.mins_before = $scope.getMinutesBeforeFromOffset();
					$scope.formEvent.absolute_date = 0;
					$scope.formEvent.relative = 1;
			}
			else if ($scope.formEvent.reminder_type == 7) {
				$scope.formEvent.absolute_date = 0;
				$scope.formEvent.mins_before = 0;
				$scope.formEvent.relative = 0;
			}	
			//all others translate to an absolute date
			else {
				$scope.formEvent.absolute_date = $scope.getAbsoluteDate().getTime();
				$scope.formEvent.mins_before = 0;
				$scope.formEvent.relative = 0;
			}
		}

		$scope.relativeToAbsolute = function() {
			var mins = $scope.getMinutesBeforeFromOffset();
			var obj = {
				date: new Date($scope.formEvent.event_start.getTime() - mins*60*1000),
				offset: mins/60,
				type: 5 
			}

			if (mins > 1440) {
			 	obj.type = 6;
			 	obj.offset = mins/1440;
			}

			return obj;
		}

		$scope.getAbsoluteDate = function() {
			return ($scope.formEvent.reminder_type == 3) ?
				new Date($scope.formEvent.reminder_datetime) :
				getAbsoluteDateFromSelect();
		}

		$scope.getAbsoluteDateFromSelect = function() {
			var date;
			var offset = 0;

			if ($scope.formEvent.reminder_type == 5) {
				offset = -1;
			}

			if ($scope.formEvent.reminder_type == 6) {
				offset = -$scope.formEvent.reminder_offset;
			}

			angular.copy($scope.formEvent.event_start, date);
			date.addDays(offset);
			date.setTimeByString($scope.formEvent.reminder_time);

			return date;
		}

		$scope.getMinutesBeforeFromOffset = function() {
			var offsetMultiplier = [1,60,1440];
			return $scope.formEvent.reminder_offset*offsetMultiplier[$scope.formEvent.reminder_type];
		}

		$scope.checkCalendarReminder = function() {
			if (!$scope.formEvent.has_reminder) {
				angular.extend($scope.formEvent, $scope.calendarWatchers[$scope.formEvent.calendar_id].reminder);
			}
		}

		$scope.reminderDefaults = function() {
			$scope.formEvent.reminder_type = ($scope.formEvent.all_day == '1') ? 5 : 1;
			$scope.formEvent.reminder_offset = ($scope.formEvent.all_day == '1') ? null : 2;
			$scope.formEvent.reminder_time = ($scope.formEvent.all_day == '1') ? new Date("1970-01-01 "+$scope.user.settings.start_of_day) : null;
			$scope.formEvent.relative = ($scope.formEvent.all_day == '1') ? 0 : 1;
			$scope.formEvent.absolute_date = ($scope.formEvent.all_day == '1') ? null : 0;
		}	
  });