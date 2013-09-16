'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, Calendar) {
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendars = calendars;
		});
  });

