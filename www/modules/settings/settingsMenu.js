angular.module('pinwheelApp')
	.directive("settingsMenu", function() {
		return {
			restrict:"E",
			replace:true,
			transclude:true,
			template:'<li><a><span aria-hidden="true" class="icon-menu"></span></a>'+
						'<ul id="settingsMenu" class="f-dropdown settingsMenu slide-drawer top-down transition-07">'+
						    '<li settings-modal-trigger="account"><span aria-hidden="true" class="icon-cog"></span> My Account</li>'+
						    '<li settings-modal-trigger="contact_points"><span aria-hidden="true" class="icon-notebook"></span> Contact Points</li>'+
						    '<li ng-show="isOrgAdmin()" settings-modal-trigger="calendars"><span aria-hidden="true" class="icon-calendar"></span> Calendar Manager</li>'+
						    '<li ng-show="isOrgSuperAdmin()" settings-modal-trigger="admin"><span aria-hidden="true" class="icon-settings"></span> Admin</li>'+
						    '<li class="divider"></li>'+
						    '<li settings-modal-trigger="diagnostics"><span aria-hidden="true" class="icon-warning"></span> Diagnostics</li>'+
						    '<li ng-click="clearMemory()"><span aria-hidden="true" class="icon-spinner"></span> Clear Memory</li>'+
						    '<li ng-click="logout()"><span aria-hidden="true" class="icon-close"></span> Log Out</li>'+
						  '</ul>'+
					'</li>',
			link: function(scope, element, attrs) {
				element.bind({
					click: function(){
						element.find('#settingsMenu').addClass('extended');
					},
					mouseleave: function(){
						element.find('#settingsMenu').removeClass('extended');
					},
					blur: function(){
						element.find('#settingsMenu').removeClass('extended');
					}
				});
			}
		};
});
