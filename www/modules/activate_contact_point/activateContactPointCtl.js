'use strict';

angular.module('pinwheelApp')
  .controller('ActivateContactPointCtl', function ($scope, $http, $routeParams) {
  		$scope.sendRequest = function(){
			$http.post('/api/v1/user/contactPoint/activate/', $routeParams).
				success(function(data){
					console.warn(data);
					$scope.requestComplete = true;
				})
				.error(function(data){
					$scope.error_text = data.message;
					$scope.requestError = true;
				});
		}
		$scope.sendRequest();

  });