'use strict';

angular.module('pinwheelApp')
	.controller('EventCtl', function ($scope, Event, QuickAdd, $filter) {
		$scope.toggle = function(name) {
			$scope[name] = !$scope[name];
		}

		$scope.formEvent = {all_day: "0"};

		//open form for adding of new event
		$scope.add = function() {
			$scope.formEvent = new Event({
				start: new Date($filter('date')(new Date(), "M/d/yyyy h:00 a")), //get date object set to current hour
				end: new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a")), //get date object set to current hour + 1
				all_day: "0",
				has_reminder: false,
				isRepeating: false,
				calendar_id: $scope.user.settings.default_calendar
			});
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
			$scope.event.$update({id: $scope.event.id}, function(event) {
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
			$scope.event.$delete({id:$scope.event.id, version:$scope.event.version});
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
			$scope.formEvent = new Event({
				start: new Date($filter('date')(new Date(), "M/d/yyyy h:00 a")), //get date object set to current hour
				end: new Date($filter('date')((new Date()).addHours(1), "M/d/yyyy h:00 a")), //get date object set to current hour + 1
				all_day: "0",
				has_reminder: false,
				isRepeating: false,
				calendar_id: $scope.user.settings.default_calendar
			});
		}

		$scope.quickAdder = {alwaysAdvanced: true};
		//open quickadd form for new event
		$scope.quickAdd = function() {
			if ($scope.quickAdder.alwaysAdvanced) {
				$scope.add();
			}
			else {
				$scope.quickAdding = true;
				$scope.addingEvent = false;
				$scope.editingEvent = false;
			}
		}

		//save new quickadded event
		$scope.quickSave = function() {
			$scope.formEvent = new Event();
			angular.copy(QuickAdd($scope.quickAdd.text), $scope.formEvent);
			$scope.save();
		}
  });
