'use strict';

angular.module('pinwheelApp')
.directive('calendarSubscription', function(Calendar, Task, Event) {
	return {
		restrict: 'E',
		template: '<div ng-style="{backgroundColor: calendar.color}" ng-include="getTemplateUrl()"></div>',
		scope: {
			calendar: '=',
			watcher: '='
		},
		controller: function($scope, $element, $attrs, $routeParams) {
			$scope.getTemplateUrl = function() {
				return ($attrs.type == 'available') ? 
						'modules/calendar/subscriptionDirective/_view_available_subscription.html' :
						'modules/calendar/subscriptionDirective/_view_subscription.html';
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
				});
			}	

			$scope.unsubscribe = function() {
				$scope.calendar.$update({id: "unsubscribe"}, function(calendar) {
					$scope.calendar = calendar;
					$scope.calendar.recent = false;
				});
			}
		}
	}
});
