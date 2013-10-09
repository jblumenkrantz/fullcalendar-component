'use strict';

angular.module('pinwheelApp')
.directive("settingsModalTrigger", function() {
	return {
		restrict:"A",
		link: function(scope, element, attrs) {
			element.bind('click', function(){
				scope.activeTab = {};
				angular.element('#account-settings-modal').css('visibility','visible').addClass('extended');
				
				$("dd[settings-tab='"+attrs.settingsModalTrigger+"']").addClass("active").siblings().removeClass("active");
				scope.activeTab[attrs.settingsModalTrigger] = true;
				angular.element('#account-settings-modal').find('.close-reveal-modal').bind('click', function(){
					scope.closeModal();
				});
				scope.$apply();
			});
		},
		controller: function($scope, $element, $attrs){
			$scope.closeModal = function() {
				angular.element('#account-settings-modal').css('visibility','hidden').removeClass('extended');
			}
		}
	};
})
.directive('settingsInterface', function(){
	return {
		restrict: 'E',
		scope:{
			user:'='
		},
		templateUrl: 'modules/settings/settings_interface.html',
		link: function($scope, $element, $attrs){
			console.warn($scope);
		}
	}
})
.directive("settingsTab", function(){
	return {
		restrict: "A",
		replace:false,
		controller: function($scope, $element, $attrs){
			$element.bind('click', function(){
				$element.addClass("active").siblings().removeClass("active");
				angular.forEach($scope.activeTab, function(value, section){
					/* set all activeTab properties to false */
					$scope.activeTab[section] = false;
				});
				/* set the clicked tabs property to true in activeTab */
				$scope.activeTab[$attrs.settingsTab] = true;
				$scope.$apply();
			});
			//console.warn($scope);
		}
	}
});