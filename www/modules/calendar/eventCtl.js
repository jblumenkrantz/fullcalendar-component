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
				$scope.cancel();
			});
		}

		//save new event
		$scope.save = function(continuing) {
			$scope.formEvent.$save({}, function(newEvent) {
				$scope.events.push(newEvent);
				$scope.cancel(continuing);
				(continuing && angular.copy(newEvent, $scope.formEvent));
			});
		}

		//delete existing event
		$scope.delete = function() {
			$scope.event.$delete({id:$scope.event.event_id, version:$scope.event.version});
			$scope.cancel();
		}

		//cancel all event forms, reset event object
		$scope.cancel = function(continuing) {
			$scope.formEvent = new Event();
			$scope.addingEvent = continuing;
			$scope.editingEvent = false;
			$scope.quickAdding = false;
		}

		$scope.reset = function() {
			$scope.formEvent = new Event();
		}

		$scope.quickAdd = {alwaysAdvanced: false}; //grab this from user settings
		//open quickadd form for new event
		$scope.quickAdd = function() {
			$scope.quickAdding = !$scope.quickAdd.alwaysAdvanced;
			$scope.addingEvent = $scope.quickAdd.alwaysAdvanced;
			$scope.editingEvent = false;
		}

		//save new quickadded event
		$scope.quickSave = function() {
			$scope.formEvent = new Event();
			angular.copy(QuickAdd($scope.quickAdd.text), $scope.formEvent);
			$scope.save();
		}
  });
