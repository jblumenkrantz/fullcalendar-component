'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionCtl', function ($scope, Calendar) {
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope.newCalendar = {calendar_name: "baseball", color: "red", subscribed: true, active: 1, recent: true};
			$scope[name] = !$scope[name];
		}

		$scope.cancelNew = function() {
			delete $scope.newCalendar;
			$scope.toggle('addingSubscription');
		}

		$scope.saveNew = function() {
			$scope.calendars.push($scope.newCalendar);
			$scope.toggle('addingSubscription');
		}

		Calendar.query({id: 'all'}, function(calendars){
			console.log(calendars);
			$scope.calendars = calendars;
		});
  });



