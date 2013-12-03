'use strict';

angular.module('pinwheelApp')
	.controller('SubscriptionDirectiveCtl', function ($scope, $routeParams, ReminderService, CalendarAdmins) {
		
		$scope.CalendarAdmins = CalendarAdmins;

		//build calendarWatcher array
		if ($scope.watcher != undefined) {
			$scope.watcher[$scope.calendar.calendar_id] = {
				color: $scope.calendar.color,
				//reminder: ReminderService.getCalendarReminderProperties($scope.calendar)
			};
		}

		$scope.editCalendar = false;

		//open existing calendar for editing
		$scope.edit = function() {
			delete $scope.calendar.events;
			$scope.editCalendar = angular.extend({}, $scope.calendar);
			$scope.close(); //close all other open calendars
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
			$scope.editCalendar = false;
		}

		//set if a calendar's events and tasks are visible
		$scope.setShowState = function() {
			var recent = $scope.calendar.recent;
			$scope.calendar.$update({id: $scope.calendar.calendar_id}, function(calendar) {
				if(!calendar.hasOwnProperty('errno')){
					calendar.recent = recent;
					$scope.calendar = calendar;
					$("#monthCalendar").fullCalendar("refetchEvents");
				}
			});
		}
		$scope.isCalendarAdmin = function(calendar){
			return calendar.calendar_admin;
		}
		$scope.isCalendarCreator = function(calendar) {
			return ($scope.user.user_id == calendar.creator_id);
		}

		$scope.checkPermision = function(p,expectBoolean){
			/* If expectBoolean is true the function will only return a boolean value  */
			/* otherwise it will return an object with the definitive boolean value */
			/* alongside an array of orgs that have that permission set to true */
			var permission = {};
			permission.orgs = [];
			permission.definitive = false;
			angular.forEach($scope.user.permissions, function(v,k){
				if(v[p]){
					permission.definitive = true;
					permission.orgs.push({org_name:v.org_name, org_id:v.org_id});
				}
			});
			return (expectBoolean)? permission.definitive:permission;
		}
		$scope.isOrgSuperAdmin = function() {
			var exp =  /super-admin/g;
			if(exp.test($scope.user.settings.primary_org.user_role)){
				return true;
			}else{
				return false;
			}	
		}
		$scope.isOrgAdmin = function() {
/*			var exp =  /admin/g;
			if(exp.test($scope.user.settings.primary_org.user_role)){*/
				return true;
/*			}else{
				return false;
			}	*/
		}

		$scope.reminderToggle = function() {
			($scope.editCalendar.has_reminder &&
			$scope.editCalendar.reminder_pref_id == null &&
			ReminderService.reminderDefaultsEvent($scope.editCalendar, $scope.user));
		}
});
