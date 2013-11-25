'use strict';

angular.module('pinwheelApp')
  .controller('HallpassCtl', function ($scope, $http, Hallpass, Facilities, OrgUserList) {
  	  	$scope.activeTab = {activePasses:true};
  	  	$scope.viewing_history = [];
  	  	Hallpass.query({}, function(hallpasses){
			$scope.hallpasses = hallpasses;
			  	console.warn(['Hallpasses loaded',$scope.hallpasses]);
		});
		Facilities.query({}, function(facilities){
			$scope.facilities = facilities;
			  	console.warn(['Facilities loaded',$scope.facilities]);
		});

		$scope.$watch('user',function(){
			if($scope.user){
				OrgUserList.query({id:$scope.user.permissions[0].org_id}, function(users){
					$scope.orgUsers = users;
					console.warn(['OrgUsers loaded',$scope.orgUsers]);
				});
			}
		});
		

		$scope.newHallpassTime = new Date(); //get date object set to next hour
			
		$scope.check_in = function(pass){
			console.warn(pass);
			pass.$update({id: pass.hallpass_id}, function(hallpass){
				console.warn(hallpass);
			});
		}
		$scope.issuePass = function(){
			var pass = new Hallpass($scope.newHallpass);
			pass.authority_user_id = $scope.user.user_id;
			pass.authority_first_name = $scope.user.first_name;
			pass.authority_last_name = $scope.user.last_name;
			pass.out_time = $scope.newHallpassTime;
			pass.$save({}, function(hallpass){
				$scope.hallpasses.push(hallpass);
			});
		}
		$scope.checkPermission = function(p,expectBoolean){
			// If expectBoolean is true the function will only return a boolean value 
			// otherwise it will return an object with the definitive boolean value 
			// alongside an array of orgs that have that permission set to true 
			var permission = {};
			permission.orgs = [];
			permission.definitive = false;
			if($scope.user != undefined){
				angular.forEach($scope.user.permissions, function(v,k){
					if(v[p]){
						permission.definitive = true;
						permission.orgs.push({org_name:v.org_name, org_id:v.org_id});
					}
				});
			}
			return (expectBoolean)? permission.definitive:permission;
		}
		$scope.viewUserHistory = function(pass){
			if(!$scope.activeTab.hasOwnProperty(pass.pass_holder_user_id)){
				$scope.viewing_history.push(pass);
			}
			angular.element("#hallpass-tabs").siblings().removeClass("active");
			angular.forEach($scope.activeTab, function(value, section){
				 //set all activeTab properties to false 
				$scope.activeTab[section] = false;
			});
			$scope.activeTab[pass.pass_holder_user_id] = true;
		}
		$scope.closeUserHistory = function(pass){
			angular.forEach($scope.viewing_history, function(user,key){
				if(user.pass_holder_user_id == pass.pass_holder_user_id){
					$scope.viewing_history.splice(key,1);
					delete $scope.activeTab[user.pass_holder_user_id];
				}
			});
			angular.forEach($scope.activeTab, function(value, section){
				 //set all activeTab properties to false 
				$scope.activeTab[section] = false;
			});
			$scope.activeTab.activePasses = true;
		}
  });