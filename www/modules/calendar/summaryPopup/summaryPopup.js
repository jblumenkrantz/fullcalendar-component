'use strict';

angular.module('pinwheelApp')
.directive('summaryPopup', function($filter, Event, Task, timeDisplayFormat, longDisplayFormat, Debounce) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/calendar/summaryPopup/_summary_popup.html',
		link: function(scope, element, attrs) {
			var popUpWidth = element.outerWidth();
			var popUpOffset = 9;
			var headerHeight = $("#mainHeader").height();
			var mainContent = $("#main-content");

			//open summary and copy resource data
			scope.openSummary = function(event, clickEvent) {
				console.log(event);
				scope.event = event;
				scope.summaryPopup = angular.extend({}, {
					isTask: event.hasOwnProperty('task_notes'),
					description: (event.hasOwnProperty('task_notes')) ? event.task_notes : event.event_description,
					hasReminder: (!!event.reminder_pref_id || !!event.source.reminder_pref_id),
					dateString: getDateString(event.start, event.end, event.allDay, event.hasOwnProperty('task_notes'))
				}, getStyle(clickEvent, event.source.color, event.allDay, scope.view));
			}

			scope.summaryEdit = function(isTask) {
				if (!isTask) {
					scope.edit(scope.event);
					scope.resetSummary();
				}
			}

			scope.summaryDelete = function(isTask) {
				delete scope.event.source;
				if (isTask) {
					$("#monthCalendar").fullCalendar('removeEvents',scope.event.id);
					new Task(scope.event).$delete({id: scope.event.id, version: scope.event.version});
				}
				else {
					scope.delete(new Event(scope.event));
				}
			}

			//expand summary info
			scope.toggleExpand = function() {
				scope.summaryPopup.expand = !scope.summaryPopup.expand;
			}

			//reset summary to empty object
			scope.resetSummary = function() {
				scope.summaryPopup = false;
			}

			//returns string to display dates
			function getDateString(start, end, allDay, isTask) {
				var startStr, endStr, startFormat, endFormat;
				var isMultiDay = (end - start >= 86400000); //86400000 = 1 day in milliseconds

				//form start of string
				startFormat = (allDay) ? longDisplayFormat : longDisplayFormat+", "+timeDisplayFormat;
				startStr = $filter('date')(start, startFormat);

				//form end of string
				endFormat = (isMultiDay) ? startFormat : timeDisplayFormat;
				endStr = (isTask || (allDay && !isMultiDay)) ? "" : " - " + $filter('date')(end, endFormat);

				//return whole string
				return startStr + endStr;
			}

			//returns position and color style for the event popup
			function getStyle(clickEvent, color, isAllDay, calendarView) {
				var style = {};													//style object
				var eventBlock = $(clickEvent.target).closest(".fc-event");		//event container
				var eventBlockOffset = eventBlock.offset();						//event container offset relative to document

				//for non-all day events that appear in the day/week view place even popup where the user clicked 
				var placeWhereClicked = (!isAllDay && (calendarView == 'week' || calendarView == 'day'));			

				//set color
				style.color = color;

				//set pointer position/color defaults
				style.pointer = {
					left: 4,
					bottom: -9,
					background: color
				};

				//set container position based on view
				style.position = (placeWhereClicked) ? getClickPosition() : getEventPosition();

				//if clicked within 300px of top of calendar area, have popup appear below event/click
				if (clickEvent.pageY < 300) {
					delete style.position.bottom;
					delete style.pointer.bottom;

					var top1 = eventBlockOffset.top + eventBlock.outerHeight() - headerHeight + popUpOffset;
					var top2 = clickEvent.pageY - headerHeight + 14;

					style.position.top = (placeWhereClicked) ? top2 : top1;
					style.pointer.top = -9;
				}

				//if cut off on the left, adjust by amount cut off and reposition pointer
				var leftOverlap = style.position.left + popUpWidth - mainContent.outerWidth();
				if (leftOverlap > 0) {
					delete style.position.left;
					style.position.right = 0;
					style.pointer.left += leftOverlap;
				}

				//returns position at top left corner of event block
				function getEventPosition() {
					return {
						left: eventBlockOffset.left - mainContent.offset().left - 3,
						bottom: mainContent.outerHeight() - eventBlockOffset.top + headerHeight + popUpOffset
					};
				}

				//returns position where the user clicks
				function getClickPosition() {
					return {
						left: clickEvent.pageX - mainContent.offset().left - 14,
						bottom: $(window).height() - clickEvent.pageY + 14
					}
				}

				return style;
			}

			//bind all other clicks to close popup EXCEPT when clicking events or when clicking the popup itself
			$(document).click(function(e) {
				var notClickingEvent = (e.target.className.indexOf("fc-event") == -1);
				var notClickingSummaryPopup = (element.has($(e.target)).length == 0);
				(notClickingEvent && notClickingSummaryPopup && scope.$apply(scope.resetSummary()));
			});

			//hide popup on widow resize
			$(window).resize(Debounce(function() {
				scope.$apply(scope.resetSummary());
			}, null, true));
		}
	}
});