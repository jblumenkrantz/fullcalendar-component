'use strict';

angular.module('pinwheelApp')
.directive('reminderList', function(ReminderService) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/reminders/_reminder_list.html',
		scope: {
			reminders: "=",
			typeFilter: "="
		},
		controller: function($scope, $element, $attrs) {
			$scope.defaultReminder = function() {
				return {
					reminder_type: 1,
					mins_before: 120,
					reminder_offset: 2,
					active: true
				};
			}

			$scope.newReminder = $scope.defaultReminder();

			$scope.add = function() {
				$scope.adding = true;
			}

			$scope.save = function() {
				$scope.reminders.push($scope.newReminder);
				$scope.$$childHead.reminderForm.$setDirty();
				$scope.cancel();
			}

			$scope.cancel = function(reminder) {
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
			reminder: "=",
			typeFilter: "="
		},
		controller: function($scope, $element, $attrs) {
			$scope.rs = ReminderService;
			$scope.reminder.reminder_offset = ReminderService.getOffsetFromMinsBefore($scope.reminder);
		
			$scope.edit = function() {
				$scope.editReminder = angular.copy($scope.reminder);
				$scope.editing = true;
			}
			$scope.update = function() {
				angular.copy($scope.editReminder, $scope.reminder);
				$scope.cancel();
			}
			$scope.delete = function() {
				$scope.reminder.active = false;
				$scope.$$childHead.reminderForm.$setDirty();
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
			cancel: "&",
			editing: "=",
			typeFilter: "&"
		},
		controller: function($scope, $element, $attrs) {
			$scope.reminderTypes = ReminderService.reminderTypes;

			//when reminder_offset OR reminder_type changes, calculate minutes before
			//newVal[0] is reminder_offset
			//newVal[1] is reminder_type
			$scope.$watchCollection('[reminder.reminder_offset, reminder.reminder_type]', function(newVal) {
				if (newVal[0] == undefined && newVal[1] == undefined) return;	//prevent initial call
				$scope.reminder.mins_before = ReminderService.getMinutesBeforeFromOffset($scope.reminder);
				//console.log(newVal, ReminderService.getMinutesBeforeFromOffset($scope.reminder));
			});

			//when reminder_time OR reminder_datetime changes, calculate absolute date
			//newVal[0] is reminder_time
			//newVal[1] is reminder_datetime
			$scope.$watchCollection('[reminder.reminder_time, reminder.reminder_datetime]', function(newVal) {
				if (newVal[0] == undefined && newVal[1] == undefined) return;	//prevent initial call
				var start = new Date(); //TODO start should reference either due or event start
				$scope.reminder.absolute_date = ReminderService.getAbsoluteDate($scope.reminder, start);
				//console.log(newVal, ReminderService.getAbsoluteDate($scope.reminder, start));
			});
		}
	}
});