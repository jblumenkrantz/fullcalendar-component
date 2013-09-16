'use strict';

angular.module('pinwheelApp')
	.directive('datebookView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/datebookViewDirective/_datebook_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
			}
		}
	});






