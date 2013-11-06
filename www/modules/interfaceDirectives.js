'use strict';

angular.module('pinwheelApp')
.value("getHeight", function(element) {
	return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
})
.value("dateDisplayFormat", "M/d/yyyy")
.value("timeDisplayFormat", "h:mm a")
.value("dateFormat", "yyyy-MM-dd")
.value("timeFormat", "HH:mm:ss")
.directive('scrollPane', function(Debounce, getHeight, $timeout) {
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
.directive('spinwheel', function() {
	return {
		restrict: 'E',
		link: function(scope, element, attrs) {
			var spinnerPosition = ($(window).height() - $("#mainHeader").height())/2 - element.height()/2;
			element.css("top", spinnerPosition);	
		}
	}		
})
.directive('datetime', function($filter, dateDisplayFormat, timeDisplayFormat, dateFormat, timeFormat, DeviceService) {
	return {
		restrict: "E",
		require: "ngModel",
		template: (DeviceService.isTouch) ? "<input type='datetime-local' />" : "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			var format = (DeviceService.isTouch) ? dateFormat+"T"+timeFormat : dateDisplayFormat+" @ "+timeDisplayFormat;

			function display(modelValue) {
				return $filter('date')(modelValue, format);
			}

			function save(viewValue) {
				var v = (DeviceService.isIOS) ? viewValue.replace("T", " ").replace(/-/g, "/") : viewValue;
				return new Date(v);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			if (!DeviceService.isTouch) {
				element.datetimeEntry({
					spinnerImage: '',
					datetimeFormat: 'o/d/Y @ h:M a'
				}).change(function() {
					ngModelCtrl.$setViewValue($(this).val());
					scope.$apply();
				});
			}
		}
	}
})
.directive('date', function($filter, dateDisplayFormat, dateFormat, DeviceService) {
	return {
		restrict: "E",
		require: "ngModel",
		template: (DeviceService.isTouch) ? "<input type='date' />" : "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			var format = (DeviceService.isTouch) ? dateFormat : dateDisplayFormat;
			function display(modelValue) {
				return $filter('date')(modelValue, format);
			}

			function save(viewValue) {
				return new Date(viewValue);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			if (!DeviceService.isTouch) {
				element.datetimeEntry({
					spinnerImage: '',
					datetimeFormat: 'o/d/Y'
				}).change(function() {
					ngModelCtrl.$setViewValue($(this).val());
					scope.$apply();
				});
			}
		}
	}
})
/*
The time directive by default works with ng-models that are dates by displaying/saving just the time portion.
Either a date obj or date in milliseconds (as string OR int) will work.
Default usage: <time ng-model="xxx" />

Optionally, if you use <time db='true' ng-model="xxx" /> it will save the value as the DB's TIME format HH:mm:ss.
Use db='true' if the ng-model is directly saved in the database as a TIME type.
*/
.directive('time', function($filter, timeDisplayFormat, timeFormat, DeviceService) {
	return {
		restrict: "E",
		require: "ngModel",
		template: (DeviceService.isTouch) ? "<input type='time' />" : "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			var format = (DeviceService.isTouch) ? timeFormat : timeDisplayFormat;
			function display(modelValue) {
				return $filter('date')((attrs.db=='true') ? new Date("1970-01-01 "+modelValue) : modelValue, format);
			}

			function save(viewValue) {
				var date = new Date("1970-01-01 "+viewValue);
				return (attrs.db=='true') ? $filter('date')(date, timeFormat) : date;
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			if (!DeviceService.isTouch) {
				element.datetimeEntry({
					spinnerImage: '',
					datetimeFormat: 'h:M a'
				}).change(function() {
					ngModelCtrl.$setViewValue($(this).val());
					scope.$apply();
				});
			}
		}
	}
})
.directive("uiColorpicker", function() {
	return {
		restrict: 'E',
		require: 'ngModel',
		scope: false,
		replace: true,
		template: "<label><input class='input-small' /></label>",
		link: function(scope, element, attrs, ngModel) {
			var input = element.find('input');
			var options = angular.extend({
				color: ngModel.$viewValue,
				showPalette: true,
				showSelectionPalette: true,
				preferredFormat: "hex6",
				palette: [ ],
				localStorageKey: "spectrum.brc", // Any Spectrum with the same string will share selection
				change: function(color) {
					scope.$apply(function() {
						ngModel.$setViewValue(color.toHexString());
					});
				}
			}, scope.$eval(attrs.options));
			
			ngModel.$render = function() {
			  input.spectrum('set', ngModel.$viewValue || '');
			}
			
			input.spectrum(options);
		}
	}
})
.directive('summaryPopup', function() {
	return {
		restrict: "E",
		template: 
			"<div class='summaryPopup'>" +
				"Test Test" +
			"</div>",
		link: function(scope, element, attrs) {
			console.log("summaryPopup");
		}
	}
})
.directive('focusMe', function($timeout, $parse) {
	return {
		restrict: 'A',
		link: function(scope, element, attrs) {
			var model = $parse(attrs.focusMe);
			var delay = (attrs.delay) ? parseInt(attrs.delay) : 0;
			scope.$watchCollection(model, function(value) {
				if(value.indexOf(true) != -1) { 
					$timeout(function() {
						element.focus(); 
					}, delay);
				}
			});
		}
	};
});
