'use strict';

angular.module('pinwheelApp')
	.directive('datebookView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/datebookViewDirective/_datebook_view.html',
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






