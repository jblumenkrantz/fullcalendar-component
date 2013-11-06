'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, $http, $timeout, $routeParams, Calendar, User, localStorage, Event, Task, Timezones, ContactPoints, $route, DeviceService) {
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
			return item.id
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
					/*Event.query({id: 'all'}, function(events){
						$scope.loading_events = false;
						$scope.events = events;
						Task.query({id: 'all'}, function(tasks){
							$scope.loading_tasks = false;
							$scope.tasks = tasks;
						});
					});*/
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
		$scope.init();

		//list of reminder types for use in reminder <select ng-model='reminder_type'> 
		$scope.reminderTypes = [
			{id: 0, name: "Minutes before", type: "relative"},
			{id: 1, name: "Hours before", type: "relative"},
			{id: 2, name: "Days before", type: "relative"},
			{id: 4, name: "The same day at:", type: "absolute"},
			{id: 5, name: "The day before at:", type: "absolute"},
			{id: 6, name: "Days before at:", type: "absolute"},
			{id: 3, name: "On date:", type: "both"},
			{id: 7, name: "No reminder", type: "both"}
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
			delete $scope.calendars;
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
		
  });

