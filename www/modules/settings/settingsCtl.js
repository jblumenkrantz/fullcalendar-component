'use strict';

angular.module('pinwheelApp')
  .controller('SettingsCtl', function ($scope, $http, User, Calendar) {
  	User.get({}, function(user){
			$scope.user = user;
	});
	Calendar.query({id: 'all'}, function(calendars){
		$scope.calendar = calendars;
		console.warn(calendars);
	});
  });



