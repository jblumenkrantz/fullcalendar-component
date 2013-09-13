'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope) {
		// API CALLS GO HERE //
		$scope.calendars = [{title: 'one'}, {title: 'two'}];
		$scope.tasks = []; 
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


