'use strict';

angular.module('pinwheelApp')
	.config(function ($routeProvider) {
		$routeProvider
			.when('/calendar/:year/:month/:day', {
				templateUrl: 'modules/calendar/_view_calendar.html',
				controller: 'CalendarCtl',
				reloadOnSearch: false
			})
			.when("/handbook", {
				templateUrl: 'modules/handbook/main.html',
				controller: 'HandbookCtl'
			})
			.when("/messaging", {
				templateUrl: 'modules/messaging/main.html',
				controller: 'MessagingCtl'
			})
			.when("/reference/:page?", {
				templateUrl: 'modules/reference/main.html',
				controller: 'ReferenceCtl'
			})
			.when("/login", {
				templateUrl: 'modules/login/login.html',
				controller: 'LoginCtl'
			})
			.when("/hallpass", {
				templateUrl: 'modules/hallpass/hallpass.html',
				controller: 'HallpassCtl'
			})
			.when("/facilities", {
				templateUrl: 'modules/facilities/facilities.html',
				controller: 'FacilitiesCtl'
			})
			.when("/settings", {
				templateUrl: 'modules/settings/settings.html',
				controller: 'SettingsCtl'
			})
			.when("/new_user", {
				templateUrl: 'modules/new_user/new_user.html',
				controller: 'NewUserCtl'
			})
			.when("/forgot_password", {
				templateUrl: 'modules/forgot_password/forgot_password.html',
				controller: 'ForgotPasswordCtl'
			})
			.when("/reset_password/:reset_token", {
				templateUrl: 'modules/reset_password/reset_password.html',
				controller: 'ResetPasswordCtl'
			})
			.when("/activate_contact_point/:activation_token", {
				templateUrl: 'modules/activate_contact_point/activate_contact_point.html',
				controller: 'ActivateContactPointCtl'
			})
			.otherwise({
				redirectTo: '/calendar/'+(new Date().getFullYear())+'/'+(new Date().getMonth()+1)+'/'+(new Date().getDate())
			});
	})

