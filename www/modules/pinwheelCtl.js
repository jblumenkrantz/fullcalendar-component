'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, $http, $timeout, $routeParams, Calendar, User, localStorage, Event, Task, Timezones, ContactPoints, $route, DeviceService, ReminderService) {
		$scope.calendarWatchers = {};
		$scope.reminders = {};

		$scope.device = DeviceService;
		$scope.calendarColor = function(item) {
			return $scope.calendarWatchers[item.calendar_id].color;
		}


		$scope.isCalendarShowing = function(item) {
			return $scope.calendarWatchers[item.calendar_id] && $scope.calendarWatchers[item.calendar_id].viewing;
		}

		$scope.isTask = function(item){
			return item.hasOwnProperty("task_notes");
		}

		$scope.visibleCalendarTasks = function(){
			var ray = [];
			angular.forEach($scope.calendars, function(cal){
				if(cal.events){
					ray = ray.concat(cal.events)
				}
			});
			return ray
		}

		$scope.calendars = $scope.calendars || [];
		$scope.events = $scope.events || [];
		$scope.tasks  = $scope.tasks || [];
		$scope.init = function(){
			User.get({}, function(user){
				$scope.user = user;
				User.query({id:'new'}, function(orgs){
					$scope.loading_user = false;
					$scope.orgs = orgs;
				});
				$scope.initialUser = {};
				angular.copy(user, $scope.initialUser);
				ContactPoints.query(function(contactPoints){
					$scope.contactPoints = contactPoints;
				});
				Calendar.query({id: 'all'}, function(calendars){
					$scope.loading_calendars = false;
					$scope.calendars = calendars;
				});
			}, function(error){
				// TODO: update this and other requests
				//       include proper error logging
				var route = $route.current.$$route.originalPath;
				if(route != '/forgot_password' && route != '/reset_password/:reset_token' && route != '/new_user'){
					/* Dont redirect with these routes */
					$scope.logout();
				}
			});
		}
		/* resource queries were put into an init funciton */
		/* to accomidate the user login functions */
		$scope.$on('$routeChangeSuccess', function (ev, data) {
			if(data.controller == 'CalendarCtl'){
				$scope.init();
			}
		});

		//list of reminder types for use in reminder <select ng-model='reminder_type'> 
		$scope.reminderTypes = ReminderService.reminderTypes;
		//DUMMY REMINDERS
		$scope.reminders = [
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30},
				{reminder_pref_id: "456", reminder_type: 1, mins_before: 120},
				{reminder_pref_id: "789", reminder_type: 2, mins_before: 1440}
			],
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30},
				{reminder_pref_id: "456", reminder_type: 1, mins_before: 120}
			],
			[
				{reminder_pref_id: "123", reminder_type: 0, mins_before: 30}
			],
			[]
		];

		Timezones.query({}, function(timezones){
			$scope.timezones = timezones;
		});
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];

			/* update the users settings in the database for drawer visibility */
			if(name == 'taskDrawer' || name == 'calendarDrawer'){
				$scope.user.settings.task_drawer_visible = $scope.taskDrawer;
				$scope.user.settings.calendar_drawer_visible = $scope.calendarDrawer;
				$scope.user.$update({id: $scope.user.user_id}, function(user){
					$scope.user = user;
					$scope.initialUser = {};
					angular.copy($scope.user, $scope.initialUser);
				});
			}
		}

		$scope.logout = function(){
			/* Delete protected data */
			angular.element('#account-settings-modal').hide().css('visibility','hidden');
			angular.element('#global-modal-bg').hide();
			$scope.calendars = [];
			delete $scope.user
			delete $scope.initialUser;
			delete $scope.contactPoints;
			$scope.events = [];
			$scope.tasks = [];
			
			/* Delete users access token */
			delete localStorage['token'];
			$http.defaults.headers.common['Authorization'] = null;

			/* Redirect to login */

			$location.path('/login');
		}

		$scope.mainAreaSize = function(){
			var locMatch = $location.path().match(/calendar/);
			if($scope.calendarDrawer&&$scope.taskDrawer){
				$scope.size = 'large-8';
			}else if(($scope.calendarDrawer && !$scope.taskDrawer) || (!$scope.calendarDrawer && $scope.taskDrawer)){
				$scope.size = 'large-10';
			}else if(!$scope.calendarDrawer && !$scope.taskDrawer){
				$scope.size = 'large-12';
			}
			
			if( $scope.calendarDrawer){
				$scope.size = $scope.size+' push-2';
			}
			return $scope.size;
		}

		//ui controller stuff
		$scope.view = "month";
		//$scope.mainAreaSize();
		$scope.$watch('user', function(){
			$scope.calendarDrawer = ($scope.user == undefined) ? false:$scope.user.settings.calendar_drawer_visible;
			$scope.taskDrawer = ($scope.user == undefined) ? false:$scope.user.settings.task_drawer_visible;
			$scope.mainAreaSize();
		});
		$scope.$watch('calendarDrawer', function(){
			$timeout(function(){$('#monthCalendar').fullCalendar('render')});
		});
		$scope.$watch('taskDrawer', function(){
			$timeout(function(){$('#monthCalendar').fullCalendar('render')});
		});

		$scope.changeView = function(view) {
			$scope.view = view;
			if(view == 'week' || view == 'day'){
				var translate = 'agenda'+view.charAt(0).toUpperCase() + view.slice(1);
				$('#monthCalendar').fullCalendar('changeView',translate);
			}
			else if(view == 'month'){
				$('#monthCalendar').fullCalendar('changeView','month');
			}
		};
		$scope.checkPermission = function(p,expectBoolean){
			// If expectBoolean is true the function will only return a boolean value 
			// otherwise it will return an object with the definitive boolean value 
			// alongside an array of orgs that have that permission set to true 
			var permission = {};
			permission.orgs = [];
			permission.definitive = false;
			if($scope.user != undefined){
				angular.forEach($scope.user.permissions, function(v,k){
					if(v[p]){
						permission.definitive = true;
						permission.orgs.push({org_name:v.org_name, org_id:v.org_id});
					}
				});
			}
			return (expectBoolean)? permission.definitive:permission;
		}
		$scope.toggle_drawer = function(){
			if($(".left-nav").width() > 0){
				$(".page-wrap").css('width','100%');
				$(".left-nav").css('width','0px');
			}else{
				$(".page-wrap").css('width','calc(100% - 200px)');
				$(".left-nav").css('width','200px');
			}
			$timeout(function(){$('#monthCalendar').fullCalendar('render')},700); // Timout must be the same duration as the transiton in milliseconds.
		}
  });

