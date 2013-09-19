'use strict';

angular.module('pinwheelApp', ['ngResource', 'ui.date', 'ngRoute'])
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
				redirectTo: '/calendar/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/'+(new Date().getDate())
			});
	})
	.value("localStorage", localStorage)
	.factory('Auth', function($resource){
		return $resource('/api/v1/auth/token/:user/:pass');
	})
	.factory('Task', function($resource){
		return $resource('/api/v1/task/:id/:version', {},
			{
				update: {
					method:'PUT',
					isArray: false,
					transformRequest: function(data){
						data.due_time = new Date(data.due_time).getTime()/1000;
						console.log(data);
						return angular.toJson(data);
					},
				},
				delete: {method: 'DELETE', params: {version: ':version'}},
				query: {
					method: 'GET',
					isArray: true,
					transformRequest: function(data){
						console.log(data);
						return data;
					},
					transformResponse: function(data){
						var tasks = angular.fromJson(data);
						angular.forEach(tasks, function(task,k){
							tasks[k].due_time = new Date(task.due_time*1000);
						});
						return tasks;
					}
				},
				get: {method: 'GET'}
			});
	})
	.factory('Event', function($resource){
		return $resource('/api/v1/event/:id/:year/:month/:day', {}, {
			update: {method:'PUT'},
			query: {
					method: 'GET',
					isArray: true,
					transformRequest: function(data){
						console.log(data);
						//return data;
					},
					transformResponse: function(data){
						var events = angular.fromJson(data);
						angular.forEach(events, function(event,k){
							events[k].event_start = Date(parseInt(event.event_start));
							events[k].event_end = Date(parseInt(event.event_end));
						});
						return events;
					}
				},
		});
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
