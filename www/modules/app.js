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
			.when("/login", {
				templateUrl: 'modules/login/main.html',
				controller: 'LoginCtl'
			})
			.otherwise({
				redirectTo: '/calendar/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/23'
			});
	})
	.value("localStorage", localStorage)
	.factory('Auth', function($resource){
		return $resource('/api/v1/auth/token/:user/:pass');
	})
	.factory('Task', function($resource){
		return $resource('/api/v1/task/:id/:version', {}, {update: {method:'PUT'}, delete: {method: 'DELETE', params: {version: ':version'}}});
	})
	.factory('Event', function($resource){
		return $resource('/api/v1/event/:id', {}, {update: {method:'PUT'}});
	})
	.factory('Calendar', function($resource){
		return $resource('/api/v1/calendar/:id/:version', {}, {update: {method:'PUT'}, delete: {method: 'DELETE', params: {version: ':version'}}});
	})
	.config(['$httpProvider', function ($httpProvider) {
		$httpProvider.defaults.headers.common['Authorization'] =  localStorage['token'];
	}]);
