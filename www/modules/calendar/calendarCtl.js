'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($scope, $routeParams, $location, Calendar, Event, Task) {

		$scope.view = "list";
		$scope.calendarWatchers = {};

		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendars = calendars;
			$scope.calendars[9].viewing = true; //this is for testing: show this calendar's events and tasks
			$scope.calendars[10].viewing = true; //this is for testing: show this calendar's events and tasks

			Task.query({id: 'all'}, function(tasks){
				$scope.tasks = tasks;
			});
			Event.query({id: 'all'}, function(events){
				$scope.events = events;
			});
		}, function(error){
			// TODO: update this and other requests
			//       include proper error logging
			$scope.logout();
		});
		
		Event.query({id: 'all'}, function(events){
			$scope.events = events;
		});

		$scope.isCalendarShowing = function(item) {
			return $scope.calendarWatchers[item.calendar_id];
		}

		$scope.changeView = function(view) {
			$scope.view = view;
		};
  });


