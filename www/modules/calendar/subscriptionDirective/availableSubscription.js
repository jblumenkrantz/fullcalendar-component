'use strict';

angular.module('pinwheelApp')
.directive('availableSubscription', function() {
	return {
		restrict: 'E',
		templateUrl: 'modules/calendar/subscriptionDirective/_view_available_subscription.html',
		scope: {
			calendar: '=',
			watcher: '='
		},
		controller: 'SubscriptionDirectiveCtl'
	}
});


