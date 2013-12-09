'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionCtl', function ($scope, Calendar, ReminderService) {
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.defaultCalendar = function() {
			return {
				color: "#222",
				viewing: true,
				reminders: []
			};
		}

		$scope.newCalendar = new Calendar($scope.defaultCalendar());

		//open calendar adder / subscription finder drawer
		$scope.add = function() {
			$scope.closeAll(); //close all calendar forms
			$scope.addingSubscription = true;
		}

		//add a new calendar
		$scope.addNew = function() {
			$scope.addingCalendar = true; //open this one
		}

		//close all open calendars
		$scope.closeAll = function() {
			$scope.addingCalendar = false;
			angular.forEach($scope.calendarWatchers, function(calObj, calID) {
				if (calObj.editingCalendar) {
					calObj.editingCalendar = false;
					return;
				}
			});
		}

		//save a newly created calendar
		$scope.save = function() {
			$scope.newCalendar.$save({}, function(calendar) {
				calendar.active = true;
				calendar.recent = true;
				calendar.viewing = true;
				$scope.calendars.push(calendar);
				$scope.cancelNew();
			});
		}

		$scope.reset = function() {
			$scope.newCalendar = new Calendar($scope.defaultCalendar());
		}

		$scope.cancel = function() {
			angular.forEach($scope.$$childTail.recentCalendars, function(calendar) {
				if (calendar.recent) calendar.recent = false;
			});
			$scope.addingSubscription = false;
		}

		$scope.cancelNew = function() {
			$scope.reset();
			$scope.addingCalendar = false;
		}
  });