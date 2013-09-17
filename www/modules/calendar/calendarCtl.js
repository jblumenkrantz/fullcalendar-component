'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($filter, $scope, $routeParams, $location, Calendar, Event, Task) {

		$scope.view = "list";
		$scope.calendarWatchers = {};

		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendars = calendars;
			Event.query({id: 'all'}, function(events){
				$scope.events = events;
				Task.query({id: 'all'}, function(tasks){
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

		$scope.changeView = function(view) {
			$scope.view = view;
		};
  });


