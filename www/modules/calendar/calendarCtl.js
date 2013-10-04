'use strict';

angular.module('pinwheelApp')
	.controller('CalendarCtl', function ($filter, $scope, $routeParams, $location, Calendar, Event, Task) {
		$scope.calendarWatchers = {};
		$scope.reminders = {};

		$scope.isCalendarShowing = function(item) {
			return $scope.calendarWatchers[item.calendar_id] && $scope.calendarWatchers[item.calendar_id].viewing;
		}

		$scope.calendarColor = function(item) {
			return $scope.calendarWatchers[item.calendar_id].color;
		}

		$scope.moveMonths = function(dist){
			$routeParams.month = parseInt($routeParams.month)+dist;
			$routeParams.day = 1;

			if($routeParams.month > 12){
				$routeParams.year = parseInt($routeParams.year)+Math.floor($routeParams.month/12);
				$routeParams.month = $routeParams.month%12;
			}

			if($routeParams.month < 1){
				$routeParams.month = 12-(Math.ceil($routeParams.month%12));
				$routeParams.year = parseInt($routeParams.year)-Math.ceil($routeParams.month/12);
			}

			$location.path("/calendar/"+$routeParams.year+"/"+$routeParams.month+"/"+$routeParams.day);
		}
  });


