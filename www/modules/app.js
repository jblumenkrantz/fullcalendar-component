'use strict';

angular.module('pinwheelApp', ['ngResource'])
	.config(function ($routeProvider) {
		$routeProvider
			.when('/calendar/:year/:month/:day', {
				templateUrl: 'modules/calendar/_view_calendar.html',
				controller: 'CalendarCtl',
				reloadOnSearch: false
			})
			.when("/handbook", {
				templateUrl: 'modules/handbook/main.html',
				controller: 'HandbookCtl'
			})
			.when("/messaging", {
				templateUrl: 'modules/messaging/main.html',
				controller: 'MessagingCtl'
			})
			.when("/reference", {
				templateUrl: 'modules/reference/main.html',
				controller: 'ReferenceCtl'
			})
			.when("/settings", {
				templateUrl: 'modules/settings/main.html',
				controller: 'SettingsCtl'
			})
			.when("/login", {
				templateUrl: 'modules/login/main.html',
				controller: 'LoginCtl'
			})
			.otherwise({
				redirectTo: '/calendar/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/'+(new Date().getDate())
			});
	})
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
	.factory('User', function($resource){
		return $resource('/api/v1/user');
	})
	.factory('Auth', function($resource){
		return $resource('/api/v1/auth/token/:user/:pass');
	})
	.factory('User', function($resource){
		return $resource('/api/v1/user/:id', {}, {update: {method:'PUT'}});
	})
	.factory('Task', function($resource){
		return $resource('/api/v1/task/:id/:version', {}, {update: {method:'PUT'}, delete: {method: 'DELETE', params: {version: ':version'}}});
	})
	.factory('Event', function($resource){
		return $resource('/api/v1/event/:id/:version/:year/:month/:day', {}, {update: {method:'PUT'}});
	})
	.factory('Reminder', function($resource){
		return $resource('/api/v1/reminder/:id', {}, {update: {method:'PUT'}});
	})
	.factory('Calendar', function($resource){
		return $resource('/api/v1/calendar/:id/:version', {}, {update: {method:'PUT'}, delete: {method: 'DELETE', params: {version: ':version'}}});
	})
	.config(['$httpProvider', function ($httpProvider) {
		$httpProvider.defaults.headers.common['Authorization'] =  localStorage['token'];
	}]);
