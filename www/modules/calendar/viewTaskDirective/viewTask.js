'use strict';

angular.module('pinwheelApp')
.directive('viewTask', function($filter, ReminderService){
	return {
		restrict: 'E',
		replace: true,
		templateUrl: 'modules/calendar/viewTaskDirective/_view_task.html',
		scope: {
			task: '=',
			calendars: '=',
			calendarWatchers: '=',
			reminderTypes: '=',
			reminderToggle: '=',
			switchCalendar: '=',
			checkCalendarReminder: '=',
			dueDateToggle: '=',
			usingOwnReminder: '=',
			noReminder: "=",
			close: "&"
		},
		controller: function($scope, $element, $attrs, $routeParams, Task){

			$scope.edit = function() {
				$scope.close();
				$scope.taskSource = $scope.task.source;
				delete $scope.task.source;
				$scope.editTask = new Task($scope.task);
				$scope.task.editing = true;
			}

			$scope.cancel = function() {
				$scope.task.editing = false;
			}

			$scope.update = function() {
				angular.copy($scope.editTask, $scope.task);
				$scope.task.$update({id: $scope.task.id}, function(task){
					$scope.task.source = $scope.taskSource;
					$scope.task.version = task.version;
					$("#monthCalendar").fullCalendar('updateEvent', $scope.task);
					delete $scope.taskSource;
					$scope.cancel();
				});
			}

			$scope.delete = function() {
				$("#monthCalendar").fullCalendar('removeEvents',$scope.task.id);
				new Task($scope.task).$delete({id: $scope.task.id, version: $scope.task.version});
				$scope.cancel();
			}

			$scope.reminderTypeFilter = function(reminderType) {
				return (reminderType.type==$scope.useReminderType || reminderType.type=='both');
			}

			$scope.$watch("editTask.has_due_date", function(newVal) {
				$scope.useReminderType = (newVal) ? "relative" : "";
			});
		}
	}
});
