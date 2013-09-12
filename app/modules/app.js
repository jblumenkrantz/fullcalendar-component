'use strict';

angular.module('pinwheelApp', [])
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
  });
