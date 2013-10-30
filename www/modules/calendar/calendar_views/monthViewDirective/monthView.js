'use strict';

angular.module('pinwheelApp')
	.directive('monthView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/monthViewDirective/_month_view.html',
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.thisMonthsEvents = function(item) {
				 	var startOfMonth = new Date($routeParams.month+'-01-'+$routeParams.year).getTime()/1000;
				 	var endOfMonth = new Date($routeParams.month*1+1+'-01-'+$routeParams.year).getTime()/1000;
				 	var itemStart = new Date((item.event_start||item.due_time)).getTime()/1000;
					return(itemStart >= startOfMonth && itemStart < endOfMonth);
				}

				$scope.eventSources = [[]];
				$scope.calendarOptions = {
					editable: true,
					header:{
					}
				};
			}
		}
	});

