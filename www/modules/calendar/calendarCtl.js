'use strict';

angular.module('pinwheelApp')
	.controller('CalendarCtl', function ($filter, $scope, $routeParams, $location, Calendar, Event, Task) {
		$scope.calendarWatchers = {};
		$scope.reminders = {};
		Calendar.query({id: 'all'}, function(calendars){
			$scope.loading_calendars = false;
			$scope.calendars = calendars;			
			Event.query({id: 'all'}, function(events){
				$scope.loading_events = false;
				$scope.events = events;
				Task.query({id: 'all'}, function(tasks){
					$scope.loading_tasks = false;
					$scope.tasks = tasks;
					angular.forEach($scope.tasks, function(task){
						if(task.due_time){
							$scope.events.push(task);
						}
					});
				});
			});
		}, function(error){
			// TODO: update this and other requests
			//       include proper error logging
			$scope.logout();
		});

		$scope.isCalendarShowing = function(item) {
			return $scope.calendarWatchers[item.calendar_id] && $scope.calendarWatchers[item.calendar_id].viewing;
		}

		$scope.calendarColor = function(item) {
			return $scope.calendarWatchers[item.calendar_id].color;
		}
  });


