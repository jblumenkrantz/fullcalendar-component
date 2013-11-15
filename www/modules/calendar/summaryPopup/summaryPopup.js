'use strict';

angular.module('pinwheelApp')
.directive('summaryPopup', function($filter, Event, timeDisplayFormat, longDisplayFormat) {
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
				scope.event = event;
				scope.resetSummary();
				scope.summaryStyle = getStyle(clickEvent, event.source.color);
				scope.summaryStyle.isTask = event.hasOwnProperty('task_notes');
				scope.summaryStyle.description = (scope.summaryStyle.isTask) ? event.task_notes : event.event_description;
				scope.summaryStyle.visible = true;
				scope.summaryStyle.dateString =  getDateString(event, scope.summaryStyle.isTask);
				scope.summaryStyle.hasEditPrivileges = true;	//TEST VALUE
				scope.summaryStyle.hasReminder = (!!event.reminder_pref_id || !!event.source.reminder_pref_id);
			}

			scope.summaryEdit = function() {
				if (!scope.summaryStyle.isTask) {
					scope.edit(scope.event);
					scope.resetSummary();
				}
			}

			scope.summaryDelete = function() {
				delete scope.event.source;
				scope.delete(new Event(scope.event));

				//TODO if task do what
			}

			//expand summary info
			scope.toggleExpand = function() {
				scope.summaryStyle.expand = !scope.summaryStyle.expand;
			}

			//reset summary to empty object
			scope.resetSummary = function() {
				scope.summaryStyle = {};
			}

			scope.resetSummary(); //initialize summary popup

			//returns string to display dates
			function getDateString(event, isTask) {
				var start, end, startFormat, endFormat, isMultiDay;

				//multiple days if range is greater that 1 day (millis)
				isMultiDay = (event.end - event.start >= 86400000);

				//form start of string
				startFormat = (event.allDay) ? longDisplayFormat : longDisplayFormat+", "+timeDisplayFormat;
				start = $filter('date')(event.start, startFormat);

				//form end of string
				endFormat = (isMultiDay) ? startFormat : timeDisplayFormat;
				end = (isTask || (event.allDay && !isMultiDay)) ? "" : " - " + $filter('date')(event.end, endFormat);

				//return whole string
				return start + end;
			}

			//returns position and color style for the event popup
			function getStyle(clickEvent, color) {
				var style = {};													//style object
				var eventBlock = $(clickEvent.target).closest(".fc-event");		//event container
				var eventBlockOffset = eventBlock.offset();						//event container offset relative to document

				style.color = color;

				//set container position
				style.position = {
					left: eventBlockOffset.left - mainContent.offset().left - 3,
					bottom: mainContent.outerHeight() - eventBlockOffset.top + headerHeight + popUpOffset
				};

				//set pointer position/color
				style.pointer = {
					left: 4,
					bottom: -9,
					background: color
				};

				//adjust position if too close to top
				if (eventBlockOffset.top <= 133) {
					delete style.bottom;
					delete style.pointer.bottom;

					style.position.top = eventBlockOffset.top + eventBlock.outerHeight() - headerHeight + popUpOffset;
					style.pointer.top = -9;
				}

				var overByLeft = (eventBlockOffset.left+popUpWidth) - (mainContent.offset().left+mainContent.outerWidth());
			
				if (overByLeft > 0) {
					delete style.position.left;
					style.position.right = 0;
					style.pointer.left += overByLeft;
				} 

				return style;
			}

			//bind all other clicks to close popup EXCEPT when clicking events or when clicking the popup itself
			$(document).click(function(e) {
				var notClickingEvent = (e.target.className.indexOf("fc-event") == -1);
				var notClickingSummaryPopup = (element.has($(e.target)).length == 0);
				(notClickingEvent && notClickingSummaryPopup && scope.$apply(scope.resetSummary()));
			});
		}
	}
});