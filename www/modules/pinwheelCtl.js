'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, Calendar, User, localStorage) {
		User.get({}, function(user){
		});
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.logout = function(){
			delete localStorage['token'];
			$location.path('/login');
		}
  });

