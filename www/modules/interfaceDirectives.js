'use strict';

angular.module('pinwheelApp')
.value("getHeight", function(element) {
	return $(window).height() - $("#mainHeader").height() - element.siblings(".scroll-header").height() + "px";
})
.value("formDatetimeFormat", "M/d/yyyy @ h:mm a")
.value("formDateFormat", "M/d/yyyy")
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
.directive('eventDatetime', function($filter, formDatetimeFormat, formDateFormat) {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function displayDate(modelValue) {
				element.datetimeEntry('option', "datetimeFormat", (scope.formEvent.all_day=='1') ? 'o/d/Y' : 'o/d/Y @ h:M a');
				return $filter('date')(modelValue, (scope.formEvent.all_day=='1') ? formDateFormat : formDatetimeFormat);
			}

			ngModelCtrl.$formatters.push(displayDate);

			element.datetimeEntry({
				spinnerImage: ''
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
			});

			scope.$watch(attrs.allDay, function(newVal) {
				//new events
				if (!scope.formEvent.hasOwnProperty("version")) {
					var format = (newVal=="1") ? "M/d/yyyy" : "M/d/yyyy h:00 a";
					var s = (scope.formEvent.event_start) ? new Date(scope.formEvent.event_start) : new Date();
					var e = (scope.formEvent.event_end) ? new Date(scope.formEvent.event_end) : new Date();
					s.setHours((new Date()).getHours());
					e.setHours((new Date()).addHours(1).getHours());
					scope.formEvent.event_start = new Date($filter('date')(s, format)); //get date object set to current hour
					scope.formEvent.event_end = new Date($filter('date')(e, format)); //get date object set to current hour + 1
				}
				//existing events
				else {
					displayDate(ngModelCtrl.$modelValue);
				}
			});
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
			function displayDate(modelValue) {
				return $filter('date')(modelValue, formDatetimeFormat);
			}

			ngModelCtrl.$formatters.push(displayDate);

			element.datetimeEntry({
				spinnerImage: '',
				datetimeFormat: 'o/d/Y @ h:M a'
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
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
			function displayDate(modelValue) {
				return $filter('date')(modelValue, formDateFormat);
			}

			ngModelCtrl.$formatters.push(displayDate);

			element.datetimeEntry({
				spinnerImage: '',
				datetimeFormat: 'o/d/Y'
			}).change(function() {
				ngModelCtrl.$setViewValue($(this).val());
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
			};
			
			input.spectrum(options);
		}
	};
})
.directive("check", function() {
	return {
		restrict: "E",
		require: "ngModel",
		template: "<input type='checkbox' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			function check() {
				return attrs.if;
			}
			ngModelCtrl.$formatters.push(check);
		}
	}
});