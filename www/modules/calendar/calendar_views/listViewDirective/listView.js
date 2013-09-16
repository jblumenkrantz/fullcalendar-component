'use strict';

angular.module('pinwheelApp')
	.directive('listView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/listViewDirective/_list_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
				console.log("list");
			}
		}
	});






