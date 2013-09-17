'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionDirectiveCtl', function ($scope, $routeParams) {
			if ($scope.watcher != undefined) {
				$scope.watcher[$scope.calendar.calendar_id] = $scope.calendar.viewing;
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
				});
				$scope.cancel('editingCalendar');
			}

			$scope.subscribe = function() {
				$scope.calendar.$update({id: "subscribe"}, function(calendar) {
					$scope.calendar = calendar;
					$scope.calendar.recent = true;
					$scope.watcher[$scope.calendar.calendar_id] = true;
				});
			}	

			$scope.unsubscribe = function() {
				$scope.calendar.$update({id: "unsubscribe"}, function(calendar) {
					$scope.calendar = calendar;
					$scope.calendar.recent = false;
					$scope.watcher[$scope.calendar.calendar_id] = false;
				});
			}

			$scope.setShowState = function() {
				$scope.calendar.viewing = $scope.watcher[$scope.calendar.calendar_id];
			}
		}
	}
});
