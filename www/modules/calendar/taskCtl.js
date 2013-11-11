'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope, Calendar, Task, ReminderService, $filter) {
		$scope.defaultTask = function() {
			return {
				has_reminder: false,
				has_due_date: false
			};
		}

  		//scope toggle function
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//create new task for later use
		$scope.newTask = new Task($scope.defaultTask());
		$scope.useReminderType = '';

		//open form for new task adding
		$scope.add = function() {
			$scope.newTask.calendar_id = $scope.user.settings.default_calendar;
			$scope.addingTask = true;
		}
		
		//save new task
		$scope.save = function() {
			$scope.newTask.$save({}, function(task) {
				$.each($scope.calendars, function(i,cal){
					if(cal.calendar_id == task.calendar_id){
						cal.events.push(task);
						$("#monthCalendar").fullCalendar('refetchEvents');
					}
				});
				$scope.cancel();
			});
		}

		//cancel new task form
		$scope.cancel = function() {
			$scope.addingTask = false;
			$scope.reset();
		}

		//reset new task and form
		$scope.reset = function() {
			$scope.newTask = new Task($scope.defaultTask());
			$scope.newTask.calendar_id = $scope.user.settings.default_calendar;
			$scope.useReminderType = '';
		}

		$scope.reminderTypeFilter = function(reminderType) {
			return (reminderType.type==$scope.useReminderType || reminderType.type=='both');
		}

		$scope.$watch("newTask.has_due_date", function(newVal) {
			$scope.useReminderType = (newVal) ? "relative" : "";
		});

		$scope.dueDateToggle = function(task) {
			if (task.has_due_date) {
				task.start = new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a"));
				(!task.has_reminder && $scope.checkCalendarReminder(task));
			}
			else {
				task.start = 0;
				(task.has_reminder && ReminderService.reminderDefaultsTask(task, $scope.user));
			}
		}

		$scope.reminderToggle = function(task) {
			//if adding reminder
			if (task.has_reminder) {
				//if no existing reminder (calendar or other), use default reminder settings
				(task.reminder_pref_id == null && ReminderService.reminderDefaultsTask(task, $scope.user));
			}
			//if removing reminder
			else {
				(task.has_due_date && $scope.checkCalendarReminder(task));
			}
		}

		$scope.switchCalendar = function(task) {
			if ((!task.has_reminder || task.using_calendar_reminder) && task.has_due_date) {
				$scope.checkCalendarReminder(task);
			}
		}

		$scope.checkCalendarReminder = function(task) {
			ReminderService.checkCalendarReminder(task, $scope.calendarWatchers);
		}

		$scope.usingOwnReminder = function(task) {
			ReminderService.usingOwnReminder(task);
		}

		$scope.noReminder = function(task) {
			ReminderService.noReminder(task);
		}
  });
