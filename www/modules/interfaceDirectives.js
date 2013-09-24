'use strict';

angular.module('pinwheelApp')
.directive('scrollPane', function(Debounce, $timeout) {
	return {
		link: function(scope, element, attrs) {
			function getHeight() {
				return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
			}

			function adjustHeight() {
				if ($(window).width() > 768) {
					$("body").scrollTop(0);
					element.css("height", getHeight());
				}
				else {
					element.css("height", "auto");
				}
			}

			adjustHeight();
	 
	 		$(window).resize(Debounce(adjustHeight));

			scope.$watch(attrs.watch, function() {
				$timeout(adjustHeight);
			});
		}
	}
});