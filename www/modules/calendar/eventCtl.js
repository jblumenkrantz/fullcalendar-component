'use strict';

angular.module('pinwheelApp')
  .controller('EventCtl', function ($scope, Event, QuickAdd) {
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		//open form for adding of new event
		$scope.add = function() {
			$scope.formEvent = new Event();
			$scope.addingEvent = true;
			$scope.editingEvent = false;
		}

		//open form for editing of existing event
		$scope.edit = function(event) {
			$scope.event = event;	//store the original event object
			$scope.formEvent = new Event();
			angular.copy($scope.event, $scope.formEvent);
			$scope.editingEvent = true;
			$scope.addingEvent = false;
		}

		//update existing event
		$scope.update = function() {
			angular.copy($scope.formEvent, $scope.event);
			$scope.event.$save({}, function(event) {
				$scope.event = event;
				$scope.formEvent = new Event();
			});
			$scope.editingEvent = false;
		}
		
		//save new event
		$scope.save = function(){
			$scope.formEvent.$save({}, function(event) {
				$scope.events.push(event);
				$scope.formEvent = new Event();
			});
			$scope.addingEvent = false;
		}

		//cancel event form
		$scope.cancel = function() {
			$scope.formEvent = new Event();
			$scope.addingEvent = false;
			$scope.editingEvent = false;
		}
  });
