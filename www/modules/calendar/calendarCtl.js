'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($scope, $routeParams, $location, Calendar, Event, Task) {

		$scope.view = "month";
		$scope.calendarWatchers = {};

		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendars = calendars;
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

		$scope.changeView = function(view){
			$scope.view = view;
		};
  });


