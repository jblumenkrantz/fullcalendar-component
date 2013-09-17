'use strict';

angular.module('pinwheelApp')
  .controller('TaskCtl', function ($scope, Calendar, Task) {
  		//scope toggle function
		$scope.toggle = function(name){
			$scope[name] = !$scope[name];
		}

		//create new task for later use
		$scope.newTask = new Task();
		
		//save new task
		$scope.saveNew = function(){
			$scope.newTask.$save({}, function(task){
				$scope.tasks.push(task);
			});
			$scope.newTask = new Task(); //refresh newTask
			$scope.toggle('addingTask');
		}
  });
