'use strict';

angular.module('pinwheelApp', ['ui.calendar', 'ngDragDrop', 'ngResource', 'ui.date', 'ngRoute', 'ngAnimate'])
	.value("localStorage", localStorage)
	.value("Debounce", function(func, threshold, execAsap) {
		var timeout;
	    return function debounced () {
	        var obj = this, args = arguments;
	        function delayed () {
	            if (!execAsap)
	                func.apply(obj, args);
	            timeout = null; 
	        };
	 
	        if (timeout)
	            clearTimeout(timeout);
	        else if (execAsap)
	            func.apply(obj, args);
	 
	        timeout = setTimeout(delayed, threshold || 100); 
	    };
	})
	.filter("inCalendar", function(){
	});
