'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($scope, $location, Calendar, Event) {
		Calendar.query({id: 'all'}, function(calendars){
			console.log(calendars);
			$scope.calendars = calendars;
		}, function(error){
			$location.path("/login");
		});
  });


