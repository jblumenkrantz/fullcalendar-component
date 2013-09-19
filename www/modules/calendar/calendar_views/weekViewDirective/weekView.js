'use strict';

angular.module('pinwheelApp')
	.directive('weekView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/weekViewDirective/_week_view.html',
			scope: {
				task: '=',
				calendars: '=',
				events: '=',
				isCalendarShowing: '&'
			},
			controller: function($scope, $element, $attrs, $routeParams, Event){
				Event.query({id: 'week',day:$routeParams.day,month:$routeParams.month,year:$routeParams.year}, function(event){
					$scope.events = event;
				});
			}
		}
	});






