'use strict';

angular.module('pinwheelApp')
	.directive('weekView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/weekViewDirective/_week_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
			}
		}
	});






