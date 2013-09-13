'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionCtl', function ($scope) {
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.calendars = []; // <- calendars api call goes here
  });



