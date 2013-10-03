'use strict';

angular.module('pinwheelApp')
  .controller('PinwheelCtl', function ($scope, $location, Calendar, User, localStorage, Event, Task) {

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
		}, function(error){
			// TODO: update this and other requests
			//       include proper error logging
			$scope.logout();
		});

		User.get({}, function(user){
			$scope.user = user;
			User.query({id:'new'}, function(orgs){
				$scope.loading_user = false;
				$scope.orgs = orgs;
			});
			$scope.initialUser = {};
			angular.copy(user, $scope.initialUser);
		});
		// nice for toggling forms. see adding a task for example.
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		$scope.logout = function(){
			delete localStorage['token'];
			$location.path('/login');
		}


		//ui controller stuff
		$scope.view = "list";
		$scope.calendarDrawer = true;
		$scope.taskDrawer = true;
		$scope.changeView = function(view) {
			$scope.view = view;
		};
  });

