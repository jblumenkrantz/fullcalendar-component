'use strict';

angular.module('pinwheelApp', ['ngResource'])
  .config(function ($routeProvider) {
    $routeProvider
      .when('/calendar/:view/:year/:month/:day', {
        templateUrl: 'modules/calendar/month.html',
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
      .otherwise({
        redirectTo: '/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/'+(new Date().getDate())
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
  	.value('guid', function(name){
		name || (name = '');
		var guid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
			return v.toString(16);
		});
		return 'test';
	})
	.factory('task', function($resource){
		return $resource('/api/task', {}, {update: {method:'PUT'}});
	});
