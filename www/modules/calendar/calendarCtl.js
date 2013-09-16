'use strict';

angular.module('pinwheelApp')
  .controller('CalendarCtl', function ($scope, $location, Calendar, Event) {
		Calendar.query({id: 'all'}, function(calendars){
			console.log(calendars);
			$scope.calendars = calendars;
		}, function(error){
			$location.path("/login");
		});

		$scope.viewableCalendars = function(item) {
			console.log(item.calendar_id);
			console.log($scope.calendars);
			return true;
		}	
  });


