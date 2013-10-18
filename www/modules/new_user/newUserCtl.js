'use strict';

angular.module('pinwheelApp')
  .controller('NewUserCtl', function ($scope, $http, NewUser, User, $location, Auth, localStorage) {
  		NewUser.query({}, function(newUser){
			$scope.orgs = newUser;
		});
  		$scope.timezones = $scope.$parent.timezones;
  		$scope.register = new User();
		
		$scope.submitNewUser = function(){
			var temp_pw = $scope.register.password;
			$scope.register.$save({id: 'create'}, function(user){
				Auth.get({user: user.user_handle, pass: temp_pw}, function(auth){
					localStorage['token'] = $http.defaults.headers.common['Authorization'] =  auth.authFields;
					$location.path('/calendar/month/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/23');
					delete $scope.username;
					delete $scope.password;
					delete $scope.register;
					temp_pw = null;
					$scope.init();
				});
			});
		}
		$scope.validateUserName = function(){
			$http.get('/api/v1/user/validate/username/'+$scope.register.user_handle+'/').
				success(function(data){
					$scope.validUsername = true;
				})
				.error(function(data){
					$scope.validUsername = false;
				});
		}
		$scope.validateEmailFormat = function() {
			var email = $scope.register.email;
   			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
   			return re.test(email);
		}
		$scope.isEmailAvailable = function() {
			if($scope.validateEmailFormat()){
				$scope.validEmailFormat = true;
				$http.get('/api/v1/user/validate/email/'+$scope.register.email+'/').
					success(function(data){
						$scope.emailAvailable = true;
					})
					.error(function(data){
						$scope.emailAvailable = false;
					});
			}else{
				$scope.validEmailFormat = false;
				$scope.emailAvailable = false;
			}
		}
		$scope.passwordsMatch = function(){
			$scope.invalidPasswords = ($scope.register.password == $scope.register.repassword)? false:true;
		}
		$scope.validateNewUserForm = function(){
			var valid = true;
			angular.forEach(angular.element('#new-user-form :input'), function(input){
				if(angular.element(input).val() == ''){
					angular.element(input).addClass('required fail');
					valid = false;
				}
				else{
					angular.element(input).removeClass('required fail');
				}
			});
			if(!$scope.validUsername || !$scope.emailAvailable){
				valid = false;
			}
			if($scope.invalidPasswords){
				valid = false;
			}
			if(valid){
				$scope.submitNewUser();
			}
		}
  });



