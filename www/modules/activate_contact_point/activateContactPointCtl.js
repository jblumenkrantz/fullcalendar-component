'use strict';

angular.module('pinwheelApp')
  .controller('ActivateContactPointCtl', function ($scope, $http, $routeParams) {
  		$scope.requestComplete = false;
  		$scope.requestError = false;
  		$scope.sendRequest = function(){
  			$scope.reset.pw_reset_token = $routeParams.reset_token;
			$http.put('/api/v1/user/password/',$scope.reset).
				success(function(data){
					$scope.requestComplete = true;
				})
				.error(function(data){
					$scope.error_text = data.message;
					$scope.requestError = true;
				});
		}

		$scope.validateResetForm = function(){
			var valid = true;
			
			if($scope.reset.password != $scope.reset.repassword){
				valid = false;
			}
			if(valid){
				$scope.sendRequest();
			}
		}
  });