'use strict';

angular.module('pinwheelApp')
.directive('outerContainer', function() {
	return function(scope, element, attrs) {
		console.log($(window).height());
		element.height($(window).height() - $("#mainHeader").height());
	}
})
.directive('scrollPane', function() {
	return function(scope, element, attrs) {
		console.log(element.parent().height());
		console.log(element.siblings(".pane-header").height());
		element.height(element.parent().height()-element.siblings(".header-scroll").height());
	}
});