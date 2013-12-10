'use strict';

angular.module('pinwheelApp')
	.directive('fullcalendarView', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/calendar_views/fullcalendarDirective/_fullcalendar_view.html',
			controller: function($scope, $filter, $element, $attrs, $routeParams, Event){
				console.warn('FULLCALENDAR DIRECTIVE LOADING');
				$scope.thisMonthsEvents = function(item) {
					var startOfMonth = new Date($routeParams.month+'-01-'+$routeParams.year).getTime()/1000;
					var endOfMonth = new Date($routeParams.month*1+1+'-01-'+$routeParams.year).getTime()/1000;
					var itemStart = new Date((item.event_start||item.due_time)).getTime()/1000;
					return(itemStart >= startOfMonth && itemStart < endOfMonth);
				}
				$scope.mainAreaHeight = function(){
					var windowHeight = $(window).height();
					var mainHeaderHeight = $("#mainHeader").outerHeight();
					var contentHeadHeight = $("#content-header").outerHeight();
					var height = windowHeight - mainHeaderHeight - contentHeadHeight;
					return height;
				}

				// Adjust the padding of the side-nav li elements until the list fits the window height
				$scope.resizeSideNav = function(){
				 if (angular.element(".side-nav").outerHeight() > $scope.mainAreaHeight()){
				 	angular.element("ul.side-nav").children().css('padding',(parseInt(angular.element("ul.side-nav").children().css('padding-top')) - 1)+'px 0px')
					$scope.resizeSideNav();
				 }     
				}
				$scope.resizeSideNav();

				$scope.eventSources = function(){
					return $scope.calendars
				}
				$scope.calendarOptions = {
					editable: true,
					header:{
						left:null,
						center:null,
						right:null
					},
					month:$routeParams.month -1,
					year:$routeParams.year,
					date:$routeParams.day,
					weekMode:'liquid',
					height:$scope.mainAreaHeight(),
					allDayDefault:false,
					viewRender: function(view,element) {
						console.warn(['fullCalendar render', {path:'(modules/calendar/calendar_views/fullcalendarDirective/fullcalendarView.js)'}]);
						//on fullCalendar render, bind scroll event to close pop up summary
						$(".fc-scroll", element).scroll(function() {
							$scope.$apply($scope.resetSummary());
						});
					},
					eventClick: function(calEvent, jsEvent, view) {
						$scope.openSummary(calEvent, jsEvent);
						$scope.$apply();
					},
					eventDragStart: function() {
						$scope.resetSummary();
						$scope.$apply();
					},
					eventResizeStart: function() {
						$scope.resetSummary();
						$scope.$apply();
					},
					eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc) {
						$scope.edit(event,false);
						$scope.update();
						$scope.$apply();
						/*$scope.bak = event.source;
						delete event.source;
						var saveEvent = new Event(event);
						saveEvent.$update({id: event.id}, function(updatedEvent) {
							event.source = $scope.bak;
							event.version = updatedEvent.version;
						});
						$scope.$apply();*/
					},
					dayClick: function(date, allDay, jsEvent, view) {
						if($(jsEvent.target).hasClass('fc-day-number')){
							$scope.pinwheel.fullCalendar('gotoDate', date.getFullYear(), date.getMonth(), date.getDate());
							$scope.pinwheel.fullCalendar('changeView', 'agendaDay');
							$scope.$parent.$parent.view = 'day';
						}else if(+($scope.selected) == +(date)){
							$scope.pinwheel.fullCalendar('gotoDate', date.getFullYear(), date.getMonth(), date.getDate());
							var thisDate = (new Date()).addHours(1);
							var startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), thisDate.getHours());
							var endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), thisDate.getHours()).addHours(1);
							$scope.add({start: startDate, end: endDate});
						}else{
							$scope.selected = date;
						}
					}
				};
			}
		}
	});

