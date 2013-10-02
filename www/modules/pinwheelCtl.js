'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, Calendar, User, localStorage) {
		User.get({}, function(user){
			$scope.user = user;
			User.query({id:'new'}, function(orgs){
				$scope.loading_user = false;
				$scope.orgs = orgs;
			});
			$scope.initialUser = {};
			angular.copy(user, $scope.initialUser);
		});
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.logout = function(){
			delete localStorage['token'];
			$location.path('/login');
		}


		//ui controller stuff
		$scope.view = "list";
		$scope.calendarDrawer = true;
		$scope.taskDrawer = true;
		$scope.changeView = function(view) {
			$scope.view = view;
		};
  });

