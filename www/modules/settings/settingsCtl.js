'use strict';

angular.module('pinwheelApp')
.controller('SettingsCtl', function ($scope, $http, User, Calendar, Timezones) {
	$scope.cancel = function(name){
		angular.extend($scope.user, $scope.initialUser);
	}

	$scope.save = function(name){
		$scope.user.$update({id: $scope.user.user_id}, function(user){
			$scope.user = user;
			$scope.initialUser = {};
			angular.copy($scope.user, $scope.initialUser);
		});
	}
	$scope.userDiff = function(){
		//console.warn([angular.toJson($scope.user),angular.toJson($scope.initialUser)]);
		return (angular.toJson($scope.user) == angular.toJson($scope.initialUser));
	}
  		
});