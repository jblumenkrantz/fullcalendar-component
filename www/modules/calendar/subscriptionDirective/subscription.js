'use strict';

angular.module('pinwheelApp')
.directive('calendarSubscription', function(){
	return {
		restrict: 'E',
		templateUrl: 'modules/calendar/subscriptionDirective/_view_subscription.html',
		scope: {
			calendar: '='
		},
		controller: function($scope, $element, $attrs, $routeParams){
			$scope.edit = function(name){
				$scope.editCalendar || ($scope.editCalendar = {});
				angular.copy($scope.calendar, $scope.editCalendar);
				$scope[name] = !$scope[name];
			}

			$scope.cancel = function(name){
				delete $scope['editCalendar']; // is this helpful?
				$scope[name] = !$scope[name];
			}

			$scope.save = function(name){
				angular.copy($scope.editCalendar, $scope.calendar);
				$scope.cancel('editingCalendar');
			}
		}
	}
})
.directive('availableSubscription', function(){
	return {
		restrict: 'E',
		templateUrl: 'modules/calendar/subscriptionDirective/_view_available_subscription.html',
		scope: {
			calendar: '='
		},
		controller: function($scope, $element, $attrs, $routeParams){
			$scope.toggleSubscribed = function() {
				this.calendar.recent = !this.calendar.recent;
				this.calendar.subscribed = !this.calendar.subscribed;
			}
		}
	}
});