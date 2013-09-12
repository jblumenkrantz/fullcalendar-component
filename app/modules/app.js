'use strict';

angular.module('pinwheelApp', [])
  .config(function ($routeProvider) {
    $routeProvider
      .when('/calendar/:view/:year/:month/:day', {
        templateUrl: 'modules/calendar/month.html',
        controller: 'CalendarCtl'
      })
			.when("/handbook", {
        templateUrl: 'modules/calendar/month.html',
        controller: 'CalendarCtl'
			})
			.when("/messaging", {
        templateUrl: 'modules/calendar/month.html',
        controller: 'CalendarCtl'
			})
			.when("/reference", {
        templateUrl: 'modules/calendar/month.html',
        controller: 'CalendarCtl'
			})
      .otherwise({
        redirectTo: '/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/'+(new Date().getDate())
      });
  });
