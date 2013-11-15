'use strict';

angular.module('pinwheelApp')
.directive('summaryPopup', function($timeout, Event, timeDisplayFormat, longDisplayFormat) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/calendar/summaryPopup/_summary_popup.html',
		link: function(scope, element, attrs) {
			var popUpWidth = element.outerWidth();
			var popUpOffset = 10;
			var headerHeight = $("#mainHeader").height();
			var mainContent = $("#main-content");
			//open summary and copy resource data
			scope.openSummary = function(event, clickEvent) {
				scope.event = event;
				scope.resetSummary();
				scope.summaryStyle.isTask = event.hasOwnProperty('task_notes');
				scope.summaryStyle.visible = true;
				scope.summaryStyle.style = getStyle(clickEvent, event.source.color);
			
				scope.summaryStyle.dateString = "Mon, October 14, 8:00 AM - 9:00 AM";

				console.log(event, scope.summaryStyle.isTask);
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

			//returns position and color style for the event popup
			function getStyle(clickEvent, color) {
				var style = {};													//style object
				var eventBlock = $(clickEvent.target).closest(".fc-event");		//event container
				var eventBlockOffset = eventBlock.offset();						//event container offset relative to document

				style.background = color;
				style.left = eventBlockOffset.left - mainContent.offset().left - 1;	
				style.bottom = mainContent.outerHeight() - eventBlockOffset.top + headerHeight + popUpOffset;

				//adjust if too close to top
				if (eventBlockOffset.top <= 133) {
					delete style.bottom;
					style.top = eventBlockOffset.top + eventBlock.outerHeight() - headerHeight + popUpOffset;
				}
				
				//adjust if too close to right
				if (eventBlockOffset.left+popUpWidth > mainContent.offset().left+mainContent.outerWidth()) {
					style.left = style.left - popUpWidth + eventBlock.outerWidth();
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