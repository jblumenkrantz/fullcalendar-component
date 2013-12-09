'use strict';

angular.module('pinwheelApp')
  .controller('LoginCtl', function ($scope, $http, $location, Auth, localStorage) {
		$scope.authenticate = function(){
			Auth.get({user: $scope.username, pass: $scope.password}, function(auth){
				localStorage['token'] = $http.defaults.headers.common['Authorization'] =  auth.authFields;
				$location.path('/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/23');
				$scope.init();
				delete $scope.username;
				delete $scope.password;
			});
		}
		$scope.showNewUserForm = function(){
			$location.path('/new_user');
		}
		$scope.showForgotPasswordForm = function(){
			$location.path('/forgot_password');
		}
  });



