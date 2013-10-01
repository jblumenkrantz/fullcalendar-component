'use strict';

angular.module('pinwheelApp')
	.directive('dayView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/dayViewDirective/_day_view.html',
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.thisDaysEvents = function(item) {
				 	var startOfDay = new Date($routeParams.month+'-'+$routeParams.day+'-'+$routeParams.year).getTime()/1000;
				 	var endOfDay = new Date($routeParams.month+'-'+$routeParams.day+'-'+$routeParams.year).getTime()/1000;
				 	var itemStart = new Date(item.event_start).getTime()/1000;
				 	if(itemStart >= startOfDay && itemStart < (endOfDay+86400)){
				 		return true;
				 	}
				return false;
				}
			}
		}
	});






