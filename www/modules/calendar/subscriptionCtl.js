'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionCtl', function ($scope, Calendar) {
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.cancelNew = function() {
			delete $scope.newCalendar;
			$scope['addingSubscription'] = false;
		}

		$scope.saveNew = function() {
			console.log($scope.newCalendar);
			$scope.newCalendar = new Calendar();
			$scope.newCalendar.calendar_name = "baseball";
			$scope.newCalendar.color = "red";
			$scope.newCalendar.subscribed = true;
			$scope.newCalendar.active = 1;
			$scope.newCalendar.recent = true;
			$scope.newCalendar.$save({}, function(calendar){
				$scope.calendars.push(calendar);
			});
			$scope['addingSubscription'] = false;
		}
  });