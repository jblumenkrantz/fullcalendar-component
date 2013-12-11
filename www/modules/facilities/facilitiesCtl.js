'use strict';

angular.module('pinwheelApp')
  .controller('FacilitiesCtl', function ($scope, $location, Facilities, OrgUserList, User) {
		if(!$scope.facilities){
			Facilities.query({}, function(facilities){
				console.warn(['Facilities loaded',$scope.facilities]);
				$scope.$parent.facilities = facilities;
			});
		}
		if(!$scope.user){
			User.get({}, function(user){
				console.warn(['User loaded',user]);
				$scope.$parent.user = user;
			});
		}

		$scope.$watch('user',function(){
			if($scope.user != undefined){
				if($scope.user.permissions != undefined){
					// Check user permissions
					/*if(!$scope.checkPermission('view_hallpass_history',true)){
	  					$location.path('calendar');
	  				}*/
					OrgUserList.query({id:$scope.user.permissions[0].org_id}, function(users){
						console.warn(['OrgUsers loaded',$scope.orgUsers]);
						$scope.orgUsers = users;
					});
				}
			}
		});
		
  });