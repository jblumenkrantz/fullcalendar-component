'use strict';

angular.module('pinwheelApp', ['ngResource'])
	.config(function ($routeProvider) {
		$routeProvider
			.when('/calendar/:view/:year/:month/:day', {
				templateUrl: 'modules/calendar/_view_calendar.html',
				controller: 'CalendarCtl'
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
				redirectTo: '/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/23'
			});
		})
	.run(function($http){
		/** Fake it til you make it **/
		$http.get("/api/v1/auth/token/test/asdf").
		success(function(data){
			var token = data.authFields;
			localStorage['token'] = token;
			$http.defaults.headers.common['Authorization'] =  token;
		}).
		error(function(data){
				console.warn(['Authentication Failed',data]);
			});
	})
	.config(['$httpProvider', function ($httpProvider) {
		var token = localStorage['token'];
		$httpProvider.defaults.headers.common['Authorization'] =  token;
	}])
	.factory('Task', function($resource){
		return $resource('/api/v1/task/:id/:version', {}, {update: {method:'PUT'}, delete: {method: 'DELETE', params: {version: ':version'}}});
	})
	.factory('Event', function($resource){
		return $resource('/api/v1/event/:id', {}, {update: {method:'PUT'}});
	})
	.factory('Calendar', function($resource){
		return $resource('/api/v1/calendar/:id', {}, {update: {method:'PUT'}});
	})
