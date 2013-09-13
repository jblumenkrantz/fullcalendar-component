'use strict';

angular.module('pinwheelApp')
	.directive('calendarSubscription', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendarSubscriptionDirective/_view_subscription.html',
			scope: {
				subscription: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
			}
		}
	});



