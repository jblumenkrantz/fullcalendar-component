'use strict';

angular.module('pinwheelApp')
	.directive('viewTask', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/taskViewDirective/_view_task.html',
			scope: {
				subscription: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
			}
		}
	});




