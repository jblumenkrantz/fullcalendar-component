'use strict';

angular.module('pinwheelApp')
	.directive('viewTask', function($filter, ReminderService){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/viewTaskDirective/_view_task.html',
			scope: {
				task: '=',
				calendars: '=',
				calendarWatchers: '=',
				reminderTypes: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.task.has_due_date = $scope.task.hasOwnProperty("due_time");
				$scope.useReminderType = '';
				$scope.edit = function() {
					$scope.editTask || ($scope.editTask = {});
					angular.copy($scope.task, $scope.editTask);
					$scope.useReminderType = ($scope.editTask.has_due_date) ? 'relative' : '';

					//apply calendar reminder if task doesn't have one already AND has a due date
					(!$scope.editTask.has_reminder &&
					$scope.editTask.has_due_date &&
					$scope.checkCalendarReminder());

					$scope.editingTask = true;
				}

				$scope.cancel = function() {
					$scope.editingTask = false;
				}

				$scope.update = function() {
					angular.copy($scope.editTask, $scope.task);
					$scope.task.$update({id: $scope.task.task_id}, function(task){
						$scope.task = task;
						$scope.cancel();
					});
				}

				$scope.delete = function() {
					$scope.task.$delete({id: $scope.task.task_id, version: $scope.task.version});
					$scope.cancel();
				}

				$scope.dueDateToggle = function() {
					if ($scope.editTask.has_due_date) {
						$scope.useReminderType = 'relative';
						$scope.editTask.due_time = new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a"));
						(!$scope.editTask.has_reminder && ReminderService.checkCalendarReminder($scope.editTask, $scope.calendarWatchers));
					}
					else {
						$scope.useReminderType = '';
						$scope.editTask.due_time = 0;
						ReminderService.usingOwnReminder($scope.editTask);
						($scope.editTask.has_reminder && ReminderService.reminderDefaultsTask($scope.editTask, $scope.user));
					}
				}

				$scope.reminderTypeFilter = function(reminderType) {
					return (reminderType.type==$scope.useReminderType || reminderType.type=='both');
				}


				$scope.reminderToggle = function() {
					//if adding reminder
					if ($scope.editTask.has_reminder) {
						//if no existing reminder (calendar or other), use default reminder settings
						($scope.editTask.reminder_pref_id == null && ReminderService.reminderDefaultsTask($scope.editTask, $scope.user));
					}
					//if removing reminder
					else {
						$scope.checkCalendarReminder();
					}
				}

				$scope.checkCalendarReminder = function() {
					ReminderService.checkCalendarReminder($scope.editTask, $scope.calendarWatchers);
				}

				$scope.usingOwnReminder = function() {
					ReminderService.usingOwnReminder($scope.editTask);
				}

				$scope.noReminder = function() {
					ReminderService.noReminder($scope.editTask);
				}
			}
		}
	});
