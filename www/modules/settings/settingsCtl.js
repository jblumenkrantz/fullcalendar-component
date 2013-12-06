'use strict';

angular.module('pinwheelApp')
.controller('SettingsCtl', function ($scope, $timeout, $http, User, Calendar, Timezones, ContactPoints) {
	// Set the size of the contact points panel to match that of the account settings panel	
	$("#contact-points").height($("#account-settings").height());

	// Load Required data if this view is the landing page
	if(!$scope.$parent.user){
		Calendar.query({id: 'all'}, function(calendars){
			console.warn(['Calendars Reloaded',calendars]);
			$scope.$parent.calendars = calendars;
		});
		User.get({}, function(user){
			console.warn(['User Reloaded',user]);
			$scope.$parent.user = user;
		});
		ContactPoints.query(function(contactPoints){
			$scope.contactPoints = contactPoints;
		});
	}

	$scope.cancelUser = function(name){
		angular.extend($scope.user, $scope.initialUser);
	}

	$scope.saveUser = function(name){
		$scope.user.$update({id: $scope.user.user_id}, function(user){
			$scope.user = user;
			$scope.initialUser = {};
			angular.copy($scope.user, $scope.initialUser);
		});
	}

	$scope.resetUser = function() {
		angular.copy($scope.initialUser, $scope.user);
	}
  	
	$scope.editContactPoint = function(point){
		$scope.point = point;
		$scope.editPoint = new ContactPoints();
		angular.copy(point, $scope.editPoint);
		$scope.editingPoint = true;
		$scope.addingPoint = false;
	}
	$scope.newContactPoint = function(){
		$scope.editPoint = new ContactPoints();
		$scope.editingPoint = true;
		$scope.addingPoint = true;
	}
	$scope.cancel = function(){
		delete $scope.point;
		$scope.editPoint = {};
		$scope.editingPoint = false;
		$scope.addingPoint = false;
	}
	$scope.save = function(){
		if($scope.addingPoint){
			$scope.savePoint();
		}else{
			$scope.update();
		}
  	}	
	$scope.update = function(){
		$scope.editPoint.$update({}, function(contactPoint) {
			angular.copy(contactPoint,$scope.point);
			$scope.cancel();
		});
	}
	$scope.savePoint = function(){
		$scope.editPoint.$save({}, function(contactPoint) {
			$scope.contactPoints.push(contactPoint);
			$scope.cancel();
		});
	}
	$scope.deletePoint = function(){
		angular.forEach($scope.$parent.contactPoints, function(value,key){
			if(value.point_id == $scope.editPoint.point_id){
				value.$delete({}, function(contactPoint) {
					$scope.$parent.contactPoints.splice(key,1);
					delete $scope.point;
					delete $scope.editPoint;
					$scope.cancel();
				});
			}
		});
	}
});