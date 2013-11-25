'use strict';

angular.module('pinwheelApp')
.value("getHeight", function(element) {
	return $(window).height() - $("#mainHeader").outerHeight() - element.siblings(".scroll-header").outerHeight() + "px";
})
.value("longDisplayFormat", "EEE, MMMM d")	//Mon, October 14
.value("dateDisplayFormat", "M/d/yyyy")		//8/7/2013
.value("timeDisplayFormat", "h:mm a")		//4:05 AM
.value("dateFormat", "yyyy-MM-dd")			//2013-08-07
.value("timeFormat", "HH:mm:ss")			//16:00:00
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
.directive('datetime', function($filter, dateFormat, timeFormat, DeviceService) {
	return {
		restrict: "E",
		require: "ngModel",
		template: (DeviceService.isTouch) ? "<input type='datetime-local' />" : "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			var divider = attrs.divider ? " "+ attrs.divider +" " : "";
			var format = (DeviceService.isTouch) ? dateFormat+"T"+timeFormat : attrs.dateFormat + divider + attrs.timeFormat;

			function display(modelValue) {
				return $filter('date')(modelValue, format);
			}

			function save(viewValue) {
				var v = (DeviceService.isIOS) ? viewValue.replace("T", " ").replace(/-/g, "/") : viewValue;
				return new Date(v);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			//apply plugins if NOT touch device
			if (!DeviceService.isTouch) {
				//configure jquery-ui datepicker plugin
				var datePickerOpts = angular.extend({
					nextText: "",
					prevText: "",
					showOn: 'both',
					preventUpdate: true,
					buttonImage: 'assets/images/icon-calendar-2.png',
					onSelect: function(value, inst) {
						var time = inst.lastVal.substr(inst.lastVal.indexOf(attrs.divider)+1);
						$(this).val(value+" "+attrs.divider+time).change();
					},
					onChangeMonthYear: function(year, month, inst) {
						var time = inst.lastVal.substr(inst.lastVal.indexOf(attrs.divider)+1);
						var val = month+"/"+inst.selectedDay+"/"+year+" "+attrs.divider+time;
						$(this).val(val).change();
					}
				}, scope.$eval(attrs.jquiOpts));
				element.datepicker(datePickerOpts);

				//configure datetimeEntry plugin
				var datetimeEntryOpts = angular.extend({},scope.$eval(attrs.dteOpts));
				element.datetimeEntry(datetimeEntryOpts);

				//bind change event to update model
				element.change(function(e) {
					scope.$apply(ngModelCtrl.$setViewValue($(this).val()));
				}); 

				//toggle picker button visibility when element is hidden
				scope.$watch(attrs.ngShow, function(isVisible) {
					element.next(".ui-datepicker-trigger").toggle(isVisible);
				});

				//prevent user from selecting an end time that is before start time
				//minWatch is not passed to start time datetime directive
				scope.$watch(attrs.minWatch, function(newVal, oldVal) {
					if (angular.equals(oldVal, newVal) || scope.formEvent.allDay) return;		//prevent initial call
					var min = (new Date(newVal)).addMinutes(1);
					element.datetimeEntry("option", "minDatetime", min);
					element.datepicker("option", "minDate", min);
				});
			}
		}
	}
})
.directive('date', function($filter, dateFormat, DeviceService) {
	return {
		restrict: "E",
		require: "ngModel",
		template: (DeviceService.isTouch) ? "<input type='date' />" : "<input type='text' />",
		replace: true,
		link: function(scope, element, attrs, ngModelCtrl) {
			var format = (DeviceService.isTouch) ? dateFormat : attrs.dateFormat;
			function display(modelValue) {
				return $filter('date')(modelValue, format);
			}

			function save(viewValue) {
				return new Date(viewValue);
			}

			ngModelCtrl.$formatters.push(display);
			ngModelCtrl.$parsers.push(save);

			//apply plugins if NOT touch device
			if (!DeviceService.isTouch) {
				//configure jquery-ui datepicker plugin
				var datePickerOpts = angular.extend({
					nextText: "",
					prevText: "",
					showOn: 'both',
					preventUpdate: true,
					buttonImage: 'assets/images/icon-calendar-2.png',
					onSelect: function(value, inst) {
						$(this).val(value).change();
					},
					onChangeMonthYear: function(year, month, inst) {
						var val = month+"/"+inst.selectedDay+"/"+year;
						$(this).val(val).change();
					}
				}, scope.$eval(attrs.jquiOpts));
				element.datepicker(datePickerOpts);

				//configure KW's datetimeEntry plugin
				var datetimeEntryOpts = angular.extend({},scope.$eval(attrs.dteOpts));
				element.datetimeEntry(datetimeEntryOpts);

				//bind change event to update model
				element.change(function() {
					scope.$apply(ngModelCtrl.$setViewValue($(this).val()));
				});

				//toggle picker button when element is hidden
				scope.$watch(attrs.ngShow, function(isVisible) {
					element.next(".ui-datepicker-trigger").toggle(isVisible);
				});

				//prevent user from selecting an end time that is before start time
				//minWatch is not passed to start time datetime directive
				scope.$watch(attrs.minWatch, function(newVal, oldVal) {
					if (angular.equals(oldVal, newVal) || !scope.formEvent.allDay) return;		//prevent initial call
					var min = (new Date(newVal));
					element.datetimeEntry("option", "minDatetime", min);
					element.datepicker("option", "minDate", min);
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
					scope.$apply(ngModelCtrl.$setViewValue($(this).val()));
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
.directive('checkbox', function() {
	return {
		restrict: "E",
		template: 	"<label class='checkbox'>" +
						"<span class='icon-checkbox-{{icon}}' aria-hidden='true'></span>{{text}}" +
					"</label>",
		replace: true,
		scope: {
			model: "=ngModel",
			text: "@",
			clicker: "&ngClick"
		},
		link: function(scope, element, attrs) {
			scope.$watch("model", function(newVal, oldVal) {
				scope.icon = (newVal) ? 'checked' : 'unchecked';
				if (!angular.equals(newVal, oldVal)) scope.clicker();
			});

			element.click(function() {
				scope.model = !scope.model;
			});
		}
	}
})
.directive('resourcePanel', function($timeout) {
	return {
		restrict: 'A',
		link: function(scope, element, attrs) {
			element.on('webkitAnimationEnd oanimationend msAnimationEnd animationend', function(e) {
				console.log(e.originalEvent.animationName);

				var sp = element.closest(".scroll-pane");
				sp.toggleClass("scrolling", (sp[0].scrollHeight > sp[0].offsetHeight));

				if (e.originalEvent.animationName=='panel-open-v') {
					console.log("add done");
					element.addClass("done");
				} 
				if (e.originalEvent.animationName=='panel-close-v') {
					console.log("remove done");
					element.removeClass("done");
				} 
				
			});
		}
	};
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