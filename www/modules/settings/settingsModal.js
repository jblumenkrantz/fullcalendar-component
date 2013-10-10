'use strict';

angular.module('pinwheelApp')
.directive("settingsModalTrigger", function() {
	return {
		restrict:"A",
		link: function(scope, element, attrs) {
			element.bind('click', function(){
				scope.activeTab = {};
				angular.element('#account-settings-modal').show().css('visibility','visible');
				angular.element('#global-modal-bg').show();
				$("dd[settings-tab='"+attrs.settingsModalTrigger+"']").addClass("active").siblings().removeClass("active");
				scope.activeTab[attrs.settingsModalTrigger] = true;
				angular.element('#account-settings-modal').find('.close-reveal-modal').bind('click', function(){
					scope.closeModal();
				});
				scope.$apply();
			});
		}
	};
})
.directive('settingsInterface', function(){
	return {
		restrict: 'E',
		templateUrl: 'modules/settings/settings_interface.html',
		link: function($scope, $element, $attrs){
			/* we have to watch the user attribute that is passed in to */
			/* avoid a race condition where the test functions wont work */
			$scope.$watch($attrs.user, function(user) {
				if(user != undefined){
					$scope.user = user;
					$scope.initialUser = {};
					angular.copy($scope.user, $scope.initialUser);
					$scope.isOrgAdmin = function(){
						var exp =  /admin/g;
						return exp.test($scope.user.settings.primary_org.user_role);
					}
					$scope.isOrgSuperAdmin = function(){
						var exp =  /super-admin/g;
						return exp.test($scope.user.settings.primary_org.user_role);
					}
				}
            });
            $scope.closeModal = function() {
				angular.element('#account-settings-modal').hide().css('visibility','hidden');
				angular.element('#global-modal-bg').hide();
			}
		}
	}
})
.directive("settingsTab", function(){
	return {
		restrict: "A",
		link: function($scope, $element, $attrs, $parentCtl){
			$element.bind('click', function(){
				$scope.test = 'new';
				$element.addClass("active").siblings().removeClass("active");
				angular.forEach($scope.activeTab, function(value, section){
					/* set all activeTab properties to false */
					$scope.activeTab[section] = false;
				});
				/* set the clicked tabs property to true in activeTab */
				$scope.activeTab[$attrs.settingsTab] = true;
				$scope.$apply();
			});
		}
	}
});