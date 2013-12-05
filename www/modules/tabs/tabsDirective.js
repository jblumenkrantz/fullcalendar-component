'use strict';

angular.module('pinwheelApp')
.directive("tabs", function(){
	return {
		restrict: "E",
		templateUrl: 'modules/tabs/_tabs.html',
		link: function($scope, $element, $attrs){
			$scope.activeTab = [];
			$scope.activeTab[$attrs.default] = true;
		}
	}
})
.directive("tab", function(){
	return {
		restrict: "A",
		link: function($scope, $element, $attrs){
			if($scope.$parent.activeTab[$scope.item.name]){
				$element.addClass('active');
			}
			$element.bind('click', function(){
				$element.addClass("active").siblings().removeClass("active");
				angular.forEach($scope.$parent.list, function(value, section){
				 	$scope.$parent.activeTab[value.name] = false;
				});
				/* set the clicked tabs property to true in activeTab */
				$scope.$parent.activeTab[$scope.item.name] = true;
				$scope.$apply();
			});
		}
	}
});