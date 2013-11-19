'use strict';

angular.module('pinwheelApp')
  .controller('HallpassCtl', function ($scope, $http, User, Hallpass) {
  	  	Hallpass.query({}, function(hallpasses){
			$scope.hallpasses = hallpasses;
			  	console.warn(['Hallpasses loaded',$scope.hallpasses]);
		});
  });