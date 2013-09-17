'use strict';

angular.module('pinwheelApp')
	.directive('dayView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/dayViewDirective/_day_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '=',
				isCalendarShowing: '&'
			},
			controller: function($scope, $element, $attrs, $routeParams){
				console.log($scope.isCalendarShowing);
			}
		}
	});






