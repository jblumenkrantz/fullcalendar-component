'use strict';

angular.module('pinwheelApp')
	.directive('weekView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/weekViewDirective/_week_view.html',
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.thisWeeksEvents = function(item) {
				 	var startOfWeek = $scope.getMonday(new Date($routeParams.month+'-'+$routeParams.day+'-'+$routeParams.year));
				 	var endOfWeek = $scope.getSunday(new Date($routeParams.month+'-'+$routeParams.day+'-'+$routeParams.year));
				 	var itemStart = new Date(item.start).getTime()/1000;
				 	
				 	if(itemStart >= startOfWeek && itemStart < endOfWeek){
				 		return true;
				 	}
				return false;
				}
				$scope.getMonday = function(d) {
				  d = new Date(d);
				  var day = d.getDay(),
				      diff = d.getDate() - day + (day == 0 ? -6:1);
				  return new Date(d.setDate(diff)).getTime()/1000;
				}
				$scope.getSunday = function(d) {
				  d = new Date(d);
				  var day = d.getDay(),
				      diff = d.getDate() - day + (day == 0 ? 6:8);
				  return new Date(d.setDate(diff)).getTime()/1000;
				}
			}
		}
	});

