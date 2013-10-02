'use strict';

angular.module('pinwheelApp')
  .controller('SettingsCtl', function ($scope, $http, User, Calendar, Timezones) {
  	Calendar.query({id: 'all'}, function(calendars){
  		$scope.calendars = calendars;
		Timezones.query(function(timezone){
			$scope.timezones = timezone;
		});
	});
  });



