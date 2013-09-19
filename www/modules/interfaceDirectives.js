'use strict';

angular.module('pinwheelApp')
.directive('scrollPane', function(Debounce) {
	return function(scope, element, attrs) {
		function getHeight() {
			return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
		}

		if ($(window).width() > 768) {
			element.css("height", getHeight());
		}

		$(window).resize(Debounce(function() {
			if ($(window).width() > 768) {
				$("body").scrollTop(0);
				element.css("height", getHeight());
			}
			else {
				element.css("height", "auto");
			}
		}));
	}
});