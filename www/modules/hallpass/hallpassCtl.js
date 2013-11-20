'use strict';

angular.module('pinwheelApp')
  .controller('HallpassCtl', function ($scope, $http, Hallpass, Facilities, OrgUserList) {
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
  });