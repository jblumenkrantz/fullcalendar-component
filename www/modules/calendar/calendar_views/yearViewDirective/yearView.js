'use strict';

angular.module('pinwheelApp')
	.directive('yearView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/yearViewDirective/_year_view.html',
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.thisYearsEvents = function(item) {
				 	var startOfYear = new Date('01-01-'+$routeParams.year).getTime()/1000;
				 	var endOfYear = (new Date('12-31-'+$routeParams.year).getTime()/1000)+86400;
				 	var itemStart = new Date(item.event_start).getTime()/1000;
				 	console.warn([startOfYear,endOfYear,itemStart]);
				 	if(itemStart >= startOfYear && itemStart < endOfYear){
				 		return true;
				 	}
				return false;
				}
			}
		}
	});

