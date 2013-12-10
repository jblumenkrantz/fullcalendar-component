'use strict';

angular.module('pinwheelApp')
  .controller('HallpassCtl', function ($scope, $location, $timeout, $http, Hallpass, Facilities, OrgUserList, User) {
		$scope.mainAreaHeight = function(){
			var windowHeight = $(window).height();
			var mainHeaderHeight = $("#mainHeader").outerHeight();
			var hallpassFormHeight = $("#hallpass-form-container").outerHeight();
			var hallpassTabsHeight = $("#hallpass-tabs").outerHeight();
			var hallpassTableHeaderHeight = $(".hallpass-table-header:visible").outerHeight();
			var height = windowHeight - mainHeaderHeight - hallpassFormHeight - hallpassTabsHeight - hallpassTableHeaderHeight - 10 //for some padding insurance;
			//console.warn([windowHeight,mainHeaderHeight,hallpassFormHeight,hallpassTabsHeight, hallpassTableHeaderHeight]);
			return height;
		}
		
		$(".hallpass-list").height($scope.mainAreaHeight());
		
  	  	$scope.activeTab = {activePasses:true};
  	  	angular.element("#hallpass-tabs").find("dd[hallpass-tab='activePasses']").addClass("active").siblings().removeClass("active");
  	  	$scope.viewing_history = [];
  	  	if(!$scope.hallpasses){
	  		Hallpass.query({}, function(hallpasses){
				$scope.$parent.hallpasses = hallpasses;
					console.warn(['Hallpasses loaded',$scope.hallpasses]);
			});
  	  	}
		if(!$scope.facilities){
			Facilities.query({}, function(facilities){
				$scope.$parent.facilities = facilities;
				  	console.warn(['Facilities loaded',$scope.facilities]);
			});
		}
		if(!$scope.user){
			User.get({}, function(user){
				console.warn(['User Reloaded',user]);
				$scope.$parent.user = user;
			});
		}

		$scope.$watch('user',function(){
			if($scope.user != undefined){
				if($scope.user.permissions != undefined){
					// Check user permissions
					if(!$scope.checkPermission('view_hallpass_history',true)){
	  					$location.path('calendar');
	  				}
					OrgUserList.query({id:$scope.user.permissions[0].org_id}, function(users){
						$scope.orgUsers = users;
						console.warn(['OrgUsers loaded',$scope.orgUsers]);
					});
				}
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

		$scope.viewUserHistory = function(pass){
			if(!$scope.activeTab.hasOwnProperty(pass.pass_holder_user_id)){
				$scope.viewing_history.push(pass);
			}
			angular.element("#hallpass-tabs").children().removeClass("active");
			$timeout(function(){
				angular.element("#hallpass-tabs").find("dd[hallpass-tab="+pass.pass_holder_user_id+"]").addClass("active");
				angular.element(".hallpass-list").height($scope.mainAreaHeight());
			});
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