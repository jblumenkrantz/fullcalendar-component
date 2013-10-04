'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, Calendar, User, localStorage, Event, Task) {
		$scope.calendarWatchers = {};
		$scope.reminders = {};

		$scope.calendarColor = function(item) {
			return $scope.calendarWatchers[item.calendar_id].color;
		}

		$scope.isCalendarShowing = function(item) {
			return $scope.calendarWatchers[item.calendar_id] && $scope.calendarWatchers[item.calendar_id].viewing;
		}
		User.get({}, function(user){
			$scope.user = user;
			User.query({id:'new'}, function(orgs){
				$scope.loading_user = false;
				$scope.orgs = orgs;
			});
			$scope.initialUser = {};
			angular.copy(user, $scope.initialUser);
			Calendar.query({id: 'all'}, function(calendars){
				$scope.loading_calendars = false;
				$scope.calendars = calendars;			
				Event.query({id: 'all'}, function(events){
					$scope.loading_events = false;
					$scope.events = events;
					Task.query({id: 'all'}, function(tasks){
						$scope.loading_tasks = false;
						$scope.tasks = tasks;
						angular.forEach($scope.tasks, function(task){
							if(task.due_time){
								$scope.events.push(task);
							}
						});
					});
				});
			});
		}, function(error){
			// TODO: update this and other requests
			//       include proper error logging
			$scope.logout();
		});
		


		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.logout = function(){
			delete localStorage['token'];
			$location.path('/login');
		}

		$scope.mainAreaSize = function(){
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
		$scope.view = "list";
		$scope.calendarDrawer = true;
		$scope.taskDrawer = true;
		$scope.mainAreaSize();;
		$scope.changeView = function(view) {
			$scope.view = view;
		};
  });

