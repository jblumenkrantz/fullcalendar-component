'use strict';

angular.module('pinwheelApp')
.directive("calendarAdminModalTrigger", function() {
	return {
		restrict:"A",
		controller: 'SubscriptionDirectiveCtl',
		link: function(scope, element, attrs) {
			scope.closeModal = function() {
				angular.element('#calendar-admin-modal').hide().css('visibility','hidden');
				angular.element('#calendar-admin-modal-bg').hide();
			}
			angular.element('#calendar-admin-modal').scope().addCalendarAdmin = function(){
				var modalScope = angular.element('#calendar-admin-modal').scope();
				var newAdmin = new scope.CalendarAdmins(modalScope.newAdmin);
				var currentAdmin = false;
				angular.forEach(modalScope.calendar.admins, function(admin){
					if(admin.user_handle == newAdmin.username){
						currentAdmin = true;
					}
				});
				if(!currentAdmin){
					newAdmin.$save({id: modalScope.calendar.calendar_id}, function(admin){
						modalScope.calendar.admins.push(admin);
						modalScope.createNewCalendarAdmin = false
					});
				}else{
					modalScope.existingAdmin = true;
					console.warn('skip add user');
				}
			}
			angular.element('#calendar-admin-modal').scope().deleteCalendarAdmin = function(admin){
				var modalScope = angular.element('#calendar-admin-modal').scope();
				admin.$delete({id: scope.calendar.calendar_id}, function(deletedAdmin){
					angular.forEach(scope.calendar.admins, function(adminSource,k){
						if(adminSource.user_id == admin.user_id){
							scope.calendar.admins.splice(k,1);
							delete modalScope.newAdmin.username;
							modalScope.createNewCalendarAdmin = false
						}
						
					});
				});
			}
			element.bind('click', function(){
				angular.element('#calendar-admin-modal').scope().calendar = scope.calendar;
				angular.element('#calendar-admin-modal').show().css('visibility','visible');
				angular.element('#calendar-admin-modal-bg').show();
				angular.element('#calendar-admin-modal').find('.close-reveal-modal').bind('click', function(){
					scope.closeModal();
				});
				scope.CalendarAdmins.query({id: scope.calendar.calendar_id}, function(admins){
					scope.calendar.admins = admins;
				});
				scope.$apply();
			});
		}
	};
});