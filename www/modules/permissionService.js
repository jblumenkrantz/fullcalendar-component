'use strict';

angular.module('pinwheelApp').service('permissionService', function($rootScope) {
	$rootScope.$watch('permissions', function(){
		this.permissions = $rootScope.permissions;
		var that = this;
		angular.forEach(this.permissions, function(set){
			angular.forEach(set, function(perm,key){
				if(perm == true){
					that[key] = true;
				}
				
			});
		});
		return that;
	})

});