'use strict';

angular.module('pinwheelApp')
	.controller('SubscriptionDirectiveCtl', function ($scope, $routeParams, ReminderService, CalendarAdmins, Calendar) {
		
		//build calendarWatcher array
		if ($scope.watcher != undefined) {
			$scope.watcher[$scope.calendar.calendar_id] = {
				color: $scope.calendar.color,
				//reminder: ReminderService.getCalendarReminderProperties($scope.calendar)
			};
		}

		//this watch makes it so that color changes while in edit mode are display immediately
		//but they are only accepted when the user hits save
		$scope.$watch("editCalendar.color", function(newVal, oldVal) {
			$scope.color = ($scope.calendar.editing) ? newVal : $scope.calendar.color;
		});

		//open existing calendar for editing
		$scope.edit = function() {
			//delete $scope.calendar.events;
			$scope.editCalendar = angular.extend({}, $scope.calendar);
			$scope.close(); //close all other open calendars
			$scope.calendar.editing = true;
		}

		//update existing calendar
		$scope.update = function() {
			//if($scope.editCalendar.org_id == null){
				//$scope.editCalendar.org_id = $scope.checkPermision('modify_public_calendars').orgs[0].org_id;
			//}
			angular.copy($scope.editCalendar, $scope.calendar);
			$scope.calendar.$update({id: $scope.calendar.calendar_id}, function(calendar) {
				$scope.calendar = calendar;
				$scope.calendar.recent = $scope.editCalendar.recent;
				CalendarAdmins.query({id: $scope.calendar.calendar_id}, function(admins){
					$scope.calendar.admins = admins;
				});
				angular.extend($scope.watcher[$scope.calendar.calendar_id], {
					color: $scope.calendar.color,
					//reminder: ReminderService.getCalendarReminderProperties($scope.calendar)
				});
				$("#monthCalendar").fullCalendar('refetchEvents');
				$scope.cancel();
			});
		}

		//subscribe to a calendar
		$scope.subscribe = function() {
			$scope.calendar.viewing=true;
			$scope.calendar.$update({id: "subscribe"}, function(calendar) {
				$scope.calendar = calendar;
				$scope.calendar.recent = true;
				CalendarAdmins.query({id: $scope.calendar.calendar_id}, function(admins){
					$scope.calendar.admins = admins;
				});
				$("#monthCalendar").fullCalendar('refetchEvents');
			});
		}	

		//unsubscribe from a calendar
		$scope.unsubscribe = function() {
			$scope.calendar.$update({id: "unsubscribe"}, function(calendar) {
				$scope.calendar = calendar;
				$scope.calendar.recent = false;
				$scope.calendar.viewing = false;
				delete $scope.watcher[$scope.calendar.calendar_id];
				$("#monthCalendar").fullCalendar('refetchEvents');
			});
		}

		//cancel editing of calendar
		$scope.cancel = function() {
			$scope.editCalendar.color = $scope.calendar.color;
			$scope.calendar.editing = false;
		}

		//set if a calendar's events and tasks are visible
		$scope.setShowState = function() {
			var recent = $scope.calendar.recent;
			var storeAdmins = $scope.calendar.admins;
			$scope.calendar.$update({id: $scope.calendar.calendar_id}, function(calendar) {
				if(!calendar.hasOwnProperty('errno')){
					calendar.recent = recent;
					$scope.calendar = calendar;
					$scope.calendar.admins = storeAdmins;
					$("#monthCalendar").fullCalendar("refetchEvents");
				}
			});
		}

		$scope.checkPermission = function(p,expectBoolean,calendar){
			// If expectBoolean is true the function will only return a boolean value
			// otherwise it will return an object with the definitive boolean value
			// alongside an array of orgs that have that permission set to true
			var permission = {};
			permission.orgs = [];
			permission.definitive = false;
			angular.forEach($scope.user.permissions, function(v,k){
				if(v[p]){
					permission.definitive = true;
					permission.orgs.push({org_name:v.org_name, org_id:v.org_id});
				}
			});
			if(p == 'calendar_admin'){
				permission.definitive = calendar.calendar_admin;
			}
			if(p == 'calendar_creator'){
				permission.definitive = ($scope.user.user_id == calendar.creator_id);
			}
			return (expectBoolean)? permission.definitive:permission;
		}

		$scope.reminderToggle = function() {
			($scope.editCalendar.has_reminder &&
			$scope.editCalendar.reminder_pref_id == null &&
			ReminderService.reminderDefaultsEvent($scope.editCalendar, $scope.user));
		}
});
