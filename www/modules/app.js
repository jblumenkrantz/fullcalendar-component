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
		return $resource('/api/v1/task/:id/:version', {},
			{
				save: {
					method:'POST',
					isArray: false,
					transformRequest: function(data){
						// TODO: add if statements in case task does not have due time
						// TODO: for save and update
						data.due_time = new Date(data.due_time).getTime()/1000;
						return angular.toJson(data);
					},
					transformResponse: function(data){
						data = angular.fromJson(data);
						data.due_time = new Date(data.due_time*1000);
						return data;
					}
				},
				update: {
					method:'PUT',
					isArray: false,
					transformRequest: function(data){
						data = angular.fromJson(data);
						data.due_time = new Date(data.due_time).getTime()/1000;
						return angular.toJson(data);
					},
					transformResponse: function(data){
						var data = angular.fromJson(data);
						data.due_time = new Date(data.due_time*1000);
						return data;
					}
				},
				delete: {method: 'DELETE', params: {version: ':version'}},
				query: {
					method: 'GET',
					isArray: true,
					transformRequest: function(data){
						return data;
					},
					transformResponse: function(data){
						var tasks = angular.fromJson(data);
						angular.forEach(tasks, function(task,k){
							if(parseInt(task.due_time)){
								tasks[k].due_time = new Date(task.due_time*1000);
							}else{
								delete task.due_time
							}
						});
						return tasks;
					}
				},
				get: {method: 'GET'}
			});
	})
	.factory('Event', function($resource){
		return $resource('/api/v1/event/:id/:year/:month/:day', {}, {
			save: {
				method:'POST',
				isArray: false,
				transformRequest: function(data){
					// TODO: add if statements in case task does not have due time
					// TODO: for save and update
					data.event_start = new Date(data.event_start).getTime()/1000;
					data.event_end = new Date(data.event_end).getTime()/1000;
					return angular.toJson(data);
				},
				transformResponse: function(data){
					data = angular.fromJson(data);
					data.event_start = new Date(data.event_start*1000);
					data.event_end = new Date(data.event_end*1000);
					return data;
				}
			},
			update: {
				method:'PUT',
				isArray: false,
				transformRequest: function(data){
					data = angular.fromJson(data);
					data.event_start = new Date(data.event_start).getTime()/1000;
					data.event_end = new Date(data.event_end).getTime()/1000;
					return angular.toJson(data);
				},
				transformResponse: function(data){
					var data = angular.fromJson(data);
					data.event_start = new Date(data.event_start*1000);
					data.event_end = new Date(data.event_end*1000);
					return data;
				}
			},
			query: {
					method: 'GET',
					isArray: true,
					transformResponse: function(data){
						var events = angular.fromJson(data);
						angular.forEach(events, function(event,k){
							events[k].event_start = new Date(parseInt(event.event_start*1000));
							events[k].event_end = new Date(parseInt(event.event_end*1000));
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
