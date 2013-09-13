'use strict';

angular.module('pinwheelApp')
	.directive('viewTask', function(){
		return {
			restrict: 'E',
			templateUrl: 'modules/calendar/viewTaskDirective/_view_task.html',
			scope: {
				task: '='
			},
			controller: function($scope, $element, $attrs, $routeParams){
				$scope.edit = function(name){
					angular.copy($scope.task, $scope.editTask);
					$scope[name] = !$scope[name];
				}

				$scope.cancel = function(name){
					delete $scope['editTask']; // is this helpful?
					$scope[name] = !$scope[name];
				}

				$scope.updateTask = function(name){
					angular.copy($scope.editTask, $scope.task);
					$scope.cancel('editingTask');
				}
			}
		}
	});




