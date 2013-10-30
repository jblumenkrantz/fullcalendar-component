'use strict';

angular.module('pinwheelApp')
.directive('activeSubscription', function() {
	return {
		restrict: 'E',
		templateUrl: 'modules/calendar/subscriptionDirective/_view_subscription.html',
		scope: {
			calendar: '=',
			watcher: '=',
			user:'=',
			reminderTypes:"="
		},
		controller: 'SubscriptionDirectiveCtl'
	}
});

