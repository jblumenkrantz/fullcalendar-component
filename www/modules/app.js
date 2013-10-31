'use strict';

angular.module('pinwheelApp', ['ui.calendar', 'ngDragDrop', 'ngResource', 'ui.date', 'ngRoute', 'ngAnimate'])
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
				templateUrl: 'modules/login/login.html',
				controller: 'LoginCtl'
			})
			.when("/new_user", {
				templateUrl: 'modules/new_user/new_user.html',
				controller: 'NewUserCtl'
			})
			.when("/forgot_password", {
				templateUrl: 'modules/forgot_password/forgot_password.html',
				controller: 'ForgotPasswordCtl'
			})
			.when("/reset_password/:reset_token", {
				templateUrl: 'modules/reset_password/reset_password.html',
				controller: 'ResetPasswordCtl'
			})
			.when("/activate_contact_point/:activation_token", {
				templateUrl: 'modules/activate_contact_point/activate_contact_point.html',
				controller: 'ActivateContactPointCtl'
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
	.factory('Auth', function($resource){
		return $resource('/api/v1/auth/token/:user/:pass');
	})
	.factory('User', function($resource){
		return $resource('/api/v1/user/:id', {}, {update: {method:'PUT'}});
	})
	.factory('ContactPoints', function($resource){
		return $resource('/api/v1/user/contactPoints/', {}, {update: {method:'PUT'}});
	})	
	.factory('NewUser', function($resource){
		return $resource('/api/v1/user/new/', {}, {post: {method:'POST'}});
	})
	.factory('Task', function($resource, ReminderService){
		return $resource('/api/v1/task/:id/:version', {},
			{
				save: {
					method:'POST',
					isArray: false,
					transformRequest: function(data){
						(data.has_reminder && ReminderService.setReminderData(data, data.start));
						if(data.has_due_date && data.start){
							data.start = new Date(data.start).getTime()/1000;
							data.hasDueDate = true;
						}
						return angular.toJson(data);
					},
					transformResponse: function(data){
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						if(parseInt(data.start) && !data.has_due_date){
							data.start = new Date(data.start*1000);
							data.hasDueDate = true;
						}else{
							delete data.start;
						}
						return data;
					}
				},
				update: {
					method:'PUT',
					isArray: false,
					transformRequest: function(data) {
						(data.has_reminder && ReminderService.setReminderData(data, data.start));
						if(data.has_due_date) {
							data.start = new Date(data.start).getTime()/1000;
							data.has_due_date = true;
						}else{
							delete data.start
							delete data.has_due_date
						}
						return angular.toJson(data);
					},
					transformResponse: function(data) {
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						if(data.start && parseInt(data.start)){
							data.start = new Date(data.start*1000);
							data.has_due_date = true;
						}else{
							delete data.start
							delete data.has_due_date
						}
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
							(tasks[k].reminder_pref_id != null && ReminderService.setReminderProperties(tasks[k]));
							if(parseInt(task.start)){
								tasks[k].start = new Date(task.start*1000);
							}else{
								delete task.start
							}
						});
						return tasks;
					}
				},
				get: {method: 'GET'}
			});
	})
	.factory('Event', function($resource, ReminderService){
		return $resource('/api/v1/event/:id/:year/:month/:day/:version', {}, {
			save: {
				method:'POST',
				isArray: false,
				transformRequest: function(data){
					// TODO: add if statements in case task does not have due time
					// TODO: for save and update
					//data.using_calendar_reminder = (data.using_calendar_reminder!=undefined);
					(data.has_reminder && ReminderService.setReminderData(data, data.start));
					data.start = data.start.getTime()/1000;
					data.end = data.end.getTime()/1000;
					return angular.toJson(data);
				},
				transformResponse: function(data){
					data = angular.fromJson(data);
					data.start = new Date(data.start*1000);
					data.end = new Date(data.end*1000);
					(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
					return data;
				}
			},
			update: {
				method:'PUT',
				isArray: false,
				transformRequest: function(data) {
					//data.using_calendar_reminder = (data.using_calendar_reminder!=undefined);
					(data.has_reminder && ReminderService.setReminderData(data, data.start));
					data.start = data.start.getTime()/1000;
					data.end = data.end.getTime()/1000;
					return angular.toJson(data);
				},
				transformResponse: function(data){
					data = angular.fromJson(data);
					data.start = new Date(data.start*1000);
					data.end = new Date(data.end*1000);
					(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
					return data;
				}
			},
			query: {
					method: 'GET',
					isArray: true,
					transformResponse: function(data){
						var events = angular.fromJson(data);
						angular.forEach(events, function(event,k){
							events[k].start = new Date(parseInt(event.start*1000));
							events[k].end = new Date(parseInt(event.end*1000));
							(events[k].reminder_pref_id != null && ReminderService.setReminderProperties(events[k]));
						});
						return events;
					}
				},
		});
	})
	.factory('Reminder', function($resource){
		return $resource('/api/v1/reminder/:id', {}, {update: {method:'PUT'}});
	})
	.factory('Calendar', function($resource, ReminderService){
		return $resource('/api/v1/calendar/:id/:version', {},
			{
				save: {
					method:'POST',
					isArray: false,
					transformRequest: function(data) {
						(data.has_reminder && ReminderService.setReminderData(data));
						return angular.toJson(data);
					},
					transformResponse: function(data) {
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						return data;
					}
				},
				update: {
					method:'PUT',
					isArray: false,
					transformRequest: function(data) {
						(data.has_reminder && ReminderService.setReminderData(data));
						return angular.toJson(data);
					},
					transformResponse: function(data) {
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						return data;
					}
				},
				query: {
					method: 'GET',
					isArray: true,
					transformResponse: function(data) {
						var calendars = angular.fromJson(data);
						angular.forEach(calendars, function(calendar, k) {
							(calendars[k].reminder_pref_id != null && ReminderService.setReminderProperties(calendars[k]));
						});
						return calendars;
					}
				},
				delete: {method: 'DELETE', params: {version: ':version'}}
			});
	})
	.factory('Timezones', function($resource){
		return $resource('/timezone.json',{},{
			query: {
					method: 'GET',
					isArray: true
			}})
	})
	.config(['$httpProvider', function ($httpProvider) {
		$httpProvider.defaults.headers.common['Authorization'] =  localStorage['token'];
	}]);
