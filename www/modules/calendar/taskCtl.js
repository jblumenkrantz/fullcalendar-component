'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope, Calendar, Task, ReminderService, $filter) {
  		//create new task for later use
		$scope.newTask = new Task({
			has_reminder: false,
			has_due_date: false
		});

		$scope.useReminderType = '';

  		//scope toggle function
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//open form for new task adding
		$scope.add = function() {
			$scope.newTask.calendar_id = $scope.user.settings.default_calendar;
			$scope.addingTask = true;
		}
		
		//save new task
		$scope.save = function(){
			$scope.newTask.$save({}, function(task) {
				$scope.events.push(task);
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
			$scope.newTask = new Task();
			$scope.newTask.calendar_id = $scope.user.settings.default_calendar;
		}

		$scope.dueDateToggle = function() {
			if ($scope.newTask.has_due_date) {
				$scope.useReminderType = 'relative';
				$scope.newTask.due_time = new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a"));
				(!$scope.newTask.has_reminder && $scope.checkCalendarReminder());
			}
			else {
				$scope.useReminderType = '';
				$scope.newTask.due_time = 0;
				($scope.newTask.has_reminder && ReminderService.reminderDefaultsTask($scope.newTask, $scope.user));
			}
		}

		$scope.reminderTypeFilter = function(reminderType) {
			return (reminderType.type==$scope.useReminderType || reminderType.type=='both');
		}

		$scope.reminderToggle = function() {
			//if adding reminder
			if ($scope.newTask.has_reminder) {
				//if no existing reminder (calendar or other), use default reminder settings
				($scope.newTask.reminder_pref_id == null && ReminderService.reminderDefaultsTask($scope.newTask, $scope.user));
			}
			//if removing reminder
			else {
				$scope.checkCalendarReminder();
			}
		}

		$scope.switchCalendar = function() {
			($scope.newTask.using_calendar_reminder && $scope.checkCalendarReminder());
		}

		$scope.checkCalendarReminder = function() {
			ReminderService.checkCalendarReminder($scope.newTask, $scope.calendarWatchers);
		}

		$scope.usingOwnReminder = function() {
			ReminderService.usingOwnReminder($scope.newTask);
		}

		$scope.noReminder = function() {
			ReminderService.noReminder($scope.newTask);
		}
  });
