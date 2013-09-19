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
			$scope.quickAdding = false;
		}

		//open form for editing of existing event
		$scope.edit = function(event) {
			$scope.event = event;	//store the original event object
			$scope.formEvent = new Event();
			angular.copy($scope.event, $scope.formEvent);
			$scope.editingEvent = true;
			$scope.addingEvent = false;
			$scope.quickAdding = false;
		}

		//update existing event
		$scope.update = function() {
			angular.copy($scope.formEvent, $scope.event);
			$scope.event.$update({id: $scope.event.event_id}, function(event) {
				$scope.event = event;
				$scope.formEvent = new Event();
			});
			$scope.editingEvent = false;
		}

		//save new event
		$scope.save = function(){
			$scope.formEvent.$save({}, function(newEvent) {
				$scope.events.push(newEvent);
				$scope.formEvent = new Event();
			});
			$scope.addingEvent = false;
		}

		//delete existing event
		$scope.delete = function(){
			$scope.event.$delete({id:$scope.event.event_id, version:$scope.event.version});
			$scope.editingEvent = false;
			$scope.addingEvent = false;
		}

		//cancel all event forms, reset event object
		$scope.cancel = function() {
			$scope.formEvent = new Event();
			$scope.addingEvent = false;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		//open quickadd form for new event
		$scope.quickAdd = function() {
			$scope.quickAdding = true;
			$scope.addingEvent = false;
			$scope.editingEvent = false;
		}

		//save new quickadded event
		$scope.quickSave = function() {
			$scope.formEvent = new Event();
			angular.copy(QuickAdd($scope.quickAdd.text), $scope.formEvent);
			$scope.save();
			$scope.quickAdding = false;
		}
  });
