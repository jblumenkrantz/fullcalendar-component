'use strict';

angular.module('pinwheelApp').service('CalendarAdminService', function(OrgUserList) {
	return {
		add: function(resource, cal_id, callback){
			resource.$save({id: cal_id}, function(admin){
				callback(admin);
			});
		},
		delete: function(resource,cal_id,callback){
			resource.$delete({id: cal_id}, function(deletedAdmin){
				callback(deletedAdmin);
			});
		},
		getOrgUsers: function(org_id,callback){
			OrgUserList.query({id: org_id}, function(users){
				callback(users);
			});
		}
	}
});
