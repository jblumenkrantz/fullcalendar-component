'use strict';

angular.module('pinwheelApp')
.directive("settingsTab", function(){
	return {
		restrict: "A",
		link: function($scope, $element, $attrs, $parentCtl){
			$element.bind('click', function(){
				//console.warn($scope);
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