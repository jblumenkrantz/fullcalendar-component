'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope, Calendar, Task) {

		// API CALLS GO HERE //
		Calendar.query({id: 'all'}, function(calendars){
			$scope.calendar = calendars;
		});
		Task.query({id: 'all'}, function(tasks){
			$scope.tasks = tasks;
		});
		// ----------------- //

		$scope.newTask = {title: '', calendar: {title: ''}, note: ''};
		$scope.toggle = function(name){
			$scope.newTaskTitle = '';
			$scope.newTaskCalendar = '';
			$scope.newTaskNote = '';
			$scope[name] = !$scope[name];
		}

		$scope.saveNew = function(){
			console.log($scope.newTask);
			$scope.tasks.push({
					title: $scope.newTask.title,
					calendar: $scope.newTask.calendar.title,
					note: $scope.newTask.note
				})
			$scope.toggle('addingTask');
		}
  });