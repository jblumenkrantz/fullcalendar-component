'use strict';

angular.module('pinwheelApp', [])
  .config(function ($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: 'calendar/month.html',
        controller: 'calendar/monthCtl.js'
      })
      .otherwise({
        redirectTo: '/'
      });
  });
