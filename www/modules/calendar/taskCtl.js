'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope, Calendar, Task) {

		// API CALLS GO HERE //
		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendars = calendars;
		});
		Task.query({id: 'all'}, function(tasks){
			$scope.tasks = tasks;
		});
		// ----------------- //

		$scope.newTask = new Task();
		$scope.toggle = function(name){
			$scope.newTask.task_name = '';
			$scope.newTask.calendar = '';
			$scope.newTask.task_notes = '';
			$scope[name] = !$scope[name];
		}

		$scope.saveNew = function(){
			$scope.newTask.$save({}, function(task){
				$scope.tasks.push(task);
			});
			$scope.toggle('addingTask');
		}
  });
