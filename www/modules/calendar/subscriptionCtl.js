'use strict';

angular.module('pinwheelApp')
  .controller('SubscriptionCtl', function ($scope, Calendar, ReminderService) {
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.defaultCalendar = function() {
			return {
				color: "blue",
				viewing: true,
				has_reminder: false
			};
		}	

		$scope.newCalendar = new Calendar($scope.defaultCalendar());

		//open calendar add/subscribe drawer
		$scope.add = function() {
			$scope.addingSubscription = true;
		}

		//save a newly created calendar
		$scope.save = function() {
			$scope.newCalendar.$save({}, function(calendar) {
				calendar.recent = true;
				calendar.viewing = true;
				$scope.calendars.push(calendar);
				$scope.reset();
			});
		}

		$scope.reset = function() {
			$scope.newCalendar = new Calendar($scope.defaultCalendar());
		}

		$scope.cancel = function() {
			$scope.reset();
			angular.forEach($scope.$$childTail.recentCalendars, function(calendar) {
				calendar.recent = false;
			});
			$scope.addingSubscription = false;
		}

		$scope.reminderToggle = function() {
			($scope.newCalendar.has_reminder && ReminderService.reminderDefaultsEvent($scope.newCalendar, $scope.user));
		}

		//DUMMY REMINDERS
		$scope.reminders = [
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30},
				{reminder_pref_id: "456", reminder_type: 1, mins_before: 120},
				{reminder_pref_id: "789", reminder_type: 2, mins_before: 1440}
			],
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30},
				{reminder_pref_id: "456", reminder_type: 1, mins_before: 120}
			],
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30}
			],
			[]
		];
  });