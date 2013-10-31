'use strict';

angular.module('pinwheelApp')
.value("getHeight", function(element) {
	return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
})
.value("formDatetimeFormat", "M/d/yyyy @ h:mm a")
.value("formDateFormat", "M/d/yyyy")
.value("formTimeFormat", "h:mm a")
.value("dbTimeFormat", "HH:mm:ss")
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
.directive('datetime', function($filter, formDatetimeFormat) {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function display(modelValue) {
				return $filter('date')(modelValue, formDatetimeFormat);
			}

			function save(viewValue) {
				return new Date(viewValue);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			element.datetimeEntry({
				spinnerImage: '',
				datetimeFormat: 'o/d/Y @ h:M a'
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
				scope.$apply();
			});
		}
	}
})
.directive('date', function($filter, formDateFormat) {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function display(modelValue) {
				return $filter('date')(modelValue, formDateFormat);
			}

			function save(viewValue) {
				return new Date(viewValue);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			element.datetimeEntry({
				spinnerImage: '',
				datetimeFormat: 'o/d/Y'
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
				scope.$apply();
			});
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
.directive('time', function($filter, formTimeFormat, dbTimeFormat) {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function display(modelValue) {
				return $filter('date')((attrs.db=='true') ? new Date("1970-01-01 "+modelValue) : modelValue, formTimeFormat);
			}

			function save(viewValue) {
				var date = new Date("1970-01-01 "+viewValue);
				return (attrs.db=='true') ? $filter('date')(date, dbTimeFormat) : date;
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			element.datetimeEntry({
				spinnerImage: '',
				datetimeFormat: 'h:M a'
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
				scope.$apply();
			});
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
});
