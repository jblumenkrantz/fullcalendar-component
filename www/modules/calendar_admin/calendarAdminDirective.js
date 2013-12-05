'use strict';

angular.module('pinwheelApp')
.directive('calendarAdminList', function(CalendarAdminService, CalendarAdmins) {
	return {
		restrict: "E",
		replace: true,
		templateUrl: 'modules/calendar_admin/_calendar_admin_list.html',
		controller: function($scope, $element, $attrs) {
			$scope.toggle = function(name) {
				$scope[name] = !$scope[name];
			}
			CalendarAdminService.getOrgUsers('org_1',function(users){
				$scope.orgUserList = users;
			});

			$scope.CalendarAdmins.query({id: $scope.calendar.calendar_id}, function(admins){
				$scope.calendar.admins = admins;
			});
			
			$scope.addAdmin = function(){
				this.newAdmin = new CalendarAdmins(angular.fromJson(this.newAdmin));				
				var currentAdmin = false;
				var that = this;
				angular.forEach($scope.calendar.admins, function(admin){
					if(admin.user_handle == that.newAdmin.user_handle){
						currentAdmin = true;
					}
				});
				if(!currentAdmin){
					CalendarAdminService.add(this.newAdmin, this.calendar.calendar_id, function(admin){
						$scope.calendar.admins.push(admin);
					});
				}
				$scope.toggle('addingAdmin');
			}

			$scope.deleteAdmin = function(){
				CalendarAdminService.delete(this.admin,$scope.calendar.calendar_id, function(a){
					angular.forEach($scope.calendar.admins, function(adminSource,k){
						if(adminSource.user_id == a.user_id){
							$scope.calendar.admins.splice(k,1);
						}						
					});
				});
			}
		}
	}
})
.directive('calendarAdmin', function(CalendarAdminService) {
	return {
		restrict: "A",
		templateUrl: 'modules/calendar_admin/_admin.html'
	}
})