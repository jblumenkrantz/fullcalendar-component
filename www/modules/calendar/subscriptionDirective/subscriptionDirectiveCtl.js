'use strict';

angular.module('pinwheelApp')
	.controller('SubscriptionDirectiveCtl', function ($scope, $routeParams) {
		if ($scope.watcher != undefined) {
			$scope.watcher[$scope.calendar.calendar_id] = {viewing: $scope.calendar.viewing, color: $scope.calendar.color};
		}

		$scope.edit = function(name) {
			$scope.editCalendar || ($scope.editCalendar = {});
			angular.copy($scope.calendar, $scope.editCalendar);
			$scope[name] = !$scope[name];
		}

		$scope.cancel = function(name) {
			delete $scope['editCalendar']; // is this helpful?
			$scope[name] = !$scope[name];
		}

		$scope.save = function(name) {
			angular.copy($scope.editCalendar, $scope.calendar);
			$scope.calendar.$update({id: $scope.calendar.calendar_id}, function(calendar) {
				$scope.calendar = calendar;
				$scope.watcher[$scope.calendar.calendar_id].color = $scope.calendar.color; 
			});
			$scope.cancel('editingCalendar');
		}

		$scope.subscribe = function() {
			$scope.calendar.$update({id: "subscribe"}, function(calendar) {
				$scope.calendar = calendar;
				$scope.calendar.recent   = true;
				$scope.calendar.viewing = true;
			});
		}	

		$scope.unsubscribe = function() {
			$scope.calendar.$update({id: "unsubscribe"}, function(calendar) {
				$scope.calendar = calendar;
				$scope.calendar.recent = false;
				$scope.calendar.viewing = false;
			});
		}

		$scope.setShowState = function() {
			$scope.calendar.viewing = $scope.watcher[$scope.calendar.calendar_id];
			$scope.calendar.$update({id: $scope.calendar.calendar_id}, function(calendar) {
				$scope.calendar = calendar;
			});
		}
});
