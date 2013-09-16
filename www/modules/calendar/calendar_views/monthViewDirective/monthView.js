'use strict';

angular.module('pinwheelApp')
	.directive('monthView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/monthViewDirective/_month_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '=',
				isCalendarShowing: '&'
			},
			controller: function($scope, $element, $attrs, $routeParams){
			}
		}
	});






