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
				has_reminder: false
			};
		}	

		$scope.newCalendar = new Calendar($scope.defaultCalendar());

		//open calendar add/subscribe drawer
		$scope.add = function() {
			$scope.closeAll(); //close all calendar forms
			$scope.addingSubscription = true;
		}

		$scope.addNew = function() {
			$scope.addingCalendar = true; //open this one
		}

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

		$scope.reminderToggle = function() {
			($scope.newCalendar.has_reminder && ReminderService.reminderDefaultsEvent($scope.newCalendar, $scope.user));
		}
  });