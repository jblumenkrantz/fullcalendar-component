'use strict';

angular.module('pinwheelApp')
  .controller('ForgotPasswordCtl', function ($scope, $http) {
  		$scope.validEmail = true;
  		$scope.validUsername = true;
  		$scope.requestComplete = false;
		$scope.sendRequest = function(){
			$http.post('/api/v1/user/password/',$scope.forgot).
					success(function(data){
						$scope.requestComplete = true;
						console.warn(data);
					})
					.error(function(data){
						console.warn(data);
					});
		}
		$scope.lookupUsername = function(){
			$http.get('/api/v1/user/validate/username/'+$scope.forgot.user_handle+'/').
				success(function(data){
					$scope.validUsername = true;
				})
				.error(function(data){
					$scope.validUsername = false;
				});
		}
		$scope.validateEmailFormat = function() {
			var email = $scope.forgot.email;
   			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
   			return re.test(email);
		}
		$scope.lookupEmail = function() {
			if($scope.validateEmailFormat()){
				$http.get('/api/v1/user/validate/email/'+$scope.forgot.email+'/').
					success(function(data){
						$scope.validEmail = true;
					})
					.error(function(data){
						$scope.validEmail = false;
					});
			}
		}
		$scope.validateForgotForm = function(){
			var valid = true;
			
			if($scope.validUsername && $scope.validEmail){
				valid = false;
			}
			if(valid){
				$scope.sendRequest();
			}
		}
  });
