'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($scope, $routeParams, $location, Calendar, Event) {

		$scope.view = "month";

		Calendar.query({id: 'all'}, function(calendars){
			console.log(calendars);
			$scope.calendars = calendars;
		}, function(error){
			// TODO: update this and other requests
			//       include proper error logging
			$scope.logout();
		});

		$scope.changeView = function(view){
			$scope.view = view;
		};

  });


