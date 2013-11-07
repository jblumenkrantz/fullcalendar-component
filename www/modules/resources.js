'use strict';

angular.module('pinwheelApp')
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
						(data.has_reminder && ReminderService.setReminderData(data, "task", data.start));
						if(data.has_due_date && data.start){
							data.start = new Date(data.start).getTime()/1000;
							data.has_due_date = true;
						}
						return angular.toJson(data);
					},
					transformResponse: function(data){
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						if(parseInt(data.start) && !data.has_due_date){
							data.start = new Date(data.start*1000);
							data.has_due_date = true;
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
						(data.has_reminder && ReminderService.setReminderData(data, "task", data.start));
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
					(data.has_reminder && ReminderService.setReminderData(data, "event", data.start));
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
					(data.has_reminder && ReminderService.setReminderData(data, "event", data.start));
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
	.factory('Calendar', function($resource, ReminderService, Event){
		return $resource('/api/v1/calendar/:id/:version', {},
			{
				save: {
					method:'POST',
					isArray: false,
					transformRequest: function(data) {
						(data.has_reminder && ReminderService.setReminderData(data, "calendar"));
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
						(data.has_reminder && ReminderService.setReminderData(data, "calendar"));
						delete data.events
						return angular.toJson(data);
					},
					transformResponse: function(data) {
						data = angular.fromJson(data);
						(data.reminder_pref_id != null && ReminderService.setReminderProperties(data));
						if(!data.viewing){
							data.eventStore=data.events;
							delete data.events
							
						}
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
							if(!calendars[k].viewing){
								calendars[k].eventStore=calendars[k].events;
								delete calendars[k].events
							}
							/*angular.forEach(calendar.events, function(e, i){
								calendar.events[i] = new Event(e);
							});*/
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

