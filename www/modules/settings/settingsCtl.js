'use strict';

angular.module('pinwheelApp')
  .controller('SettingsCtl', function ($scope, $http, User, Calendar, Timezones) {
  	Calendar.query({id: 'all'}, function(calendars){
  		$scope.loading_user = false;
		$scope.calendars = calendars;
		User.get({}, function(user){
			$scope.user = user;
			User.query({id:'new'}, function(orgs){
				$scope.orgs = orgs;
			});
			$scope.initialUser = {};
			angular.copy(user, $scope.initialUser);
		});

		Timezones.query(function(timezone){
			$scope.timezones = timezone;
		});
	});
  });



