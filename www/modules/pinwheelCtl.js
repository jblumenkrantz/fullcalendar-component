'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope) {
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.calendars = []; // <- calendars api call goes here
  });

