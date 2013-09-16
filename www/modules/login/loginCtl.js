'use strict';

angular.module('pinwheelApp')
  .controller('LoginCtl', function ($scope, $http, $location, Auth, localStorage) {
		$scope.user = 'test';
		$scope.password = 'asdf';
		$scope.authenticate = function(){
			Auth.get({user: $scope.user, pass: $scope.password}, function(auth){
				localStorage['token'] = $http.defaults.headers.common['Authorization'] =  auth.authFields;
				$location.path('/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/23');
			});
		}
  });



