'use strict';

angular.module('pinwheelApp')
.directive('reminderList', function(ReminderService) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/reminders/_reminder_list.html',
		scope: {
			reminders: "="
		},
		controller: function($scope, $element, $attrs) {
			$scope.defaultReminder = function() {
				return {
					reminder_pref_id: "000",
					reminder_type: 1,
					mins_before: 120,
					reminder_offset: 2
				};
			}
			
			$scope.newReminder = $scope.defaultReminder();

			$scope.add = function() {
				$scope.adding = true;
			}
			$scope.save = function() {
				$scope.cancel();
			}
			$scope.cancel = function() {
				$scope.newReminder = $scope.defaultReminder();
				$scope.adding = false;
			}
		}
	}
})
.directive('reminder', function(ReminderService) {
	return {
		restrict: "A",
		templateUrl: 'modules/reminders/_reminder.html',
		scope: {
			reminder: "="
		},
		controller: function($scope, $element, $attrs) {
			//set description
			$scope.reminder.description = [
				"Remind me",
				$scope.reminder.reminder_offset,
				['minutes', 'hours', 'days'][$scope.reminder.reminder_type],
				"prior to start or due date."
			].join(" ");
			
			$scope.edit = function() {
				$scope.editing = true;
			}
			$scope.update = function() {
				$scope.cancel();
			}
			$scope.delete = function() {
				$element.remove();
				$scope.cancel();
			}
			$scope.cancel = function() {
				$scope.editing = false;
			}
		}
	}
})
.directive('reminderForm', function(ReminderService) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/reminders/_reminder_form.html',
		scope: {
			reminder: "=formReminder",
			save: "&",
			cancel: "&"
		},
		controller: function($scope, $element, $attrs) {
			$scope.reminderTypes = ReminderService.reminderTypes;
			$scope.reminder.reminder_offset = ReminderService.getOffsetFromMinsBefore($scope.reminder);
		}
	}
});