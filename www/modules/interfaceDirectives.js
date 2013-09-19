'use strict';

angular.module('pinwheelApp')
.directive('outerContainer', function() {
	return function(scope, element, attrs) {
		element.height($(window).height() - $("#mainHeader").height());
	}
})
.directive('scrollPane', function() {
	return function(scope, element, attrs) {
		element.height(element.parent().height()-element.siblings(".scroll-header").height());
	}
});