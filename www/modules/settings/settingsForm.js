'use strict';

angular.module('pinwheelApp')
	.directive('settingsForm', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/settings/_user_settings_form.html',
			scope: {
				user:'=',
				initialuser:'=',
				calendars: '=',
				timezones: '=',
				orgs: '=',
				save: '&',
				cancel: '&',
			},
			transclude:true,
			controller: function($scope, $element, $attrs){
				$scope.cancel = function(name){
					angular.copy($scope.initialuser, $scope.user);
				}

				$scope.save = function(name){
					$scope.user.$update({id: $scope.user.user_id}, function(user){
						$scope.user = user;
						angular.copy($scope.user, $scope.initialuser);
					});
				}
				$scope.userDiff = function(){
					return (JSON.stringify($scope.user) == JSON.stringify($scope.initialuser));
				}
			}
		}
	});
