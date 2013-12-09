'use strict';

angular.module('pinwheelApp')
.directive("hallpassTab", function(){
	return {
		restrict: "A",
		link: function($scope, $element, $attrs, $parentCtl){
			$element.bind('click', function(){
				$element.addClass("active").siblings().removeClass("active");
				angular.forEach($scope.activeTab, function(value, section){
					/* set all activeTab properties to false */
					$scope.activeTab[section] = false;
				});
				/* set the clicked tabs property to true in activeTab */
				$scope.activeTab[$attrs.hallpassTab] = true;
				$scope.$apply();
			});
		}
	}
});