'use strict';

angular.module('pinwheelApp')
.value("getHeight", function(element) {
	return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
})
.value("formDatetimeFormat", "M/d/yyyy @ h:mm a")
.value("formDateFormat", "M/d/yyyy")
.directive('scrollPane', function(Debounce, getHeight) {
	return {
		link: function(scope, element, attrs) {
			function adjustHeight() {
				if ($(window).width() > 768) {
					$("body").scrollTop(0);
					element.css("height", getHeight(element));
				}
				else {
					element.css("height", "auto");
				}
			}

			adjustHeight();
	 
	 		$(window).resize(Debounce(adjustHeight));
		}
	}
})
.directive('scrollPaneTwo', function(Debounce, $timeout, getHeight) {
	return {
		link: function(scope, element, attrs) {
			function adjustHeight() {
				element.css("height", getHeight(element))
			}

	 		adjustHeight();

	 		$(window).resize(Debounce(adjustHeight));
	 		
			scope.$watch(attrs.watch, function() {
				$timeout(adjustHeight);
			});
		}
	}
})
.directive('datetime', function($filter, formDatetimeFormat, formDateFormat) {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function displayDate(modelValue) {
				return $filter('date')(modelValue, (attrs.allDay=='1') ? formDateFormat : formDatetimeFormat);
			}

			ngModelCtrl.$formatters.push(displayDate);

			element.datetimeEntry({
				spinnerImage: ''
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
			});
			
			scope.$watch(attrs.allDay, function(newVal) {
				element.datetimeEntry('option', "datetimeFormat", (newVal=='1') ? 'o/d/Y' : 'o/d/Y @ h:M a');
			});
		}
	}
});