angular.module('pinwheelApp')
	.directive("contactPoint", function() {
		return {
			restrict:"E",
			replace:true,
			transclude:true,
			templateUrl: 'modules/contact_points/contact_point.html',
			link: function(scope, element, attrs) {

			}
		};
});
