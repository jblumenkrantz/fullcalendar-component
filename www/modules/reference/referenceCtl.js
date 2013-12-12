'use strict';

angular.module('pinwheelApp')
  .controller('ReferenceCtl', function ($scope, $routeParams) {

  	$scope.referenceIndex = [
		{	
		title:'Alcohol / Tobacco / Drugs',  // title of the section as it appears in the side menu
			pages:[  // Array of sub menu objects
				{
					title:'Substances & Your Body', // title of the sub-section as it appears in the side menu
					name:'alcohol_tobacco_drugs-001' // file name with out the extension
				},
				{
					title:'Alcohol',
					name:'alcohol_tobacco_drugs-002'
				}
			]
		},
		{	
			title:'Character',
			pages:[
				{
					title:'Substances & Your Body',
					name:'character-001'
				}
			]
		}

  	]

  	if($routeParams.hasOwnProperty('page')){
  		$scope.referencePage = $routeParams.page.split('-');
  		$scope.path = 'modules/reference/'+$scope.referencePage[0]+'/';
  		$scope.resource = $scope.referencePage[0]+'-'+$scope.referencePage[1]+'.html';
  	}else{
  		$scope.path = 'modules/reference/dev_guide/';
  		$scope.resource = 'dev_guide.html';
  	}
  });
