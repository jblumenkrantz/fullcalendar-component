'use strict';

angular.module('pinwheelApp')
	.directive('viewTask', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/viewTaskDirective/_view_task.html',
			scope: {
				task: '=',
				calendars: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.edit = function(name){
					$scope.editTask || ($scope.editTask = {});
					angular.copy($scope.task, $scope.editTask);
					$scope[name] = !$scope[name];
				}

				$scope.cancel = function(name){
					delete $scope['editTask']; // is this helpful?
					$scope[name] = !$scope[name];
				}

				$scope.updateTask = function(name){
					angular.copy($scope.editTask, $scope.task);
					$scope.task.$update({id: $scope.task.task_id}, function(task){
						$scope.task = task;
					});
					$scope.cancel('editingTask');
				}
				$scope.deleteTask = function(task){
					//console.warn('delete');
					$scope.task.$delete({id: $scope.task.task_id}, function(task){
						console.warn(task);
						$scope.task = task;
					});
					$scope.cancel('editingTask');
				}
			}
		}
	});




