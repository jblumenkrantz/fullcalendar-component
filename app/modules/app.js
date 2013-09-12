'use strict';

angular.module('pinwheelApp', [])
  .config(function ($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: 'modules/calendar/month.html',
        controller: 'MonthCtl'
      })
      .otherwise({
        redirectTo: '/'
      });
  });
