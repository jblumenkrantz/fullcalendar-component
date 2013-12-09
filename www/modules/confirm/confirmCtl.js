'use strict';

angular.module('pinwheelApp')
	.directive("confirm", function() {
		return {
				restrict:"E",
				transclude:true,
				replace:true,
				scope:{
					header:"@",
					body:"@",
					footer:"@",
					negativeText:"@",
					affirmativeText:"@",
					negativeAction:"&",
					affirmativeAction:"&",
					buttonClass:"@",
					buttonIcon:"@",
					disabled:"&",
					buttonFloat:"@",
					enforceDialog:"@"
				},
				template: '<button ng-click="openModal()" ng-disabled="disabled()" class="icon modalTrigger button {{buttonFloat}} {{buttonClass}}">'+
							'<span aria-hidden="true" class="{{buttonIcon}}" ng-transclude> </span>'+
						'</button>',
				link: function(scope, element, attrs) {
					// convert the green and red classes to the BRC classes save and cancel
					// this is just here for ease of use
					attrs.buttonClass = (RegExp(/green/i).test(attrs.buttonClass))? 'save':attrs.buttonClass;
					attrs.buttonClass = (RegExp(/red/i).test(attrs.buttonClass))? 'cancel':attrs.buttonClass;

					var mod = angular.element('#global-modal');
					var screen = angular.element('#global-modal-bg');

					scope.openModal = function(){
						var defaults = {
									buttonClass:"",
									buttonIcon:"icon-checkmark",
									header:"Attention!",
									body:"Are you sure you want to continue?",
									footer:"",
									negativeText:"No",
									affirmativeText:"Yes",
									enforceDialog:true
						};
						// Apply default values if the attribute was not sent
						angular.forEach(defaults, function (value, key)	{
							mod.scope()[key] = (attrs[key] === undefined)? value:attrs[key];
							mod.scope().enforceDialog = (attrs.enforceDialog == "false")? false:true;
						});
						if(mod.scope().enforceDialog){
							screen.show();
							mod.show().css('visibility','visible');
							angular.element('#global-modal-bg').show();
						}else{
							//console.warn('bypass modal');
							scope.affirmativeAction();
						}
						mod.scope().negative = function(){
							scope.closeModal();
							scope.negativeAction();
						}
						mod.scope().affirmative = function(){
							scope.closeModal();
							scope.affirmativeAction();
						}
					}

					scope.closeModal = function(){
						mod.hide();
						if(!angular.element('#account-settings-modal').is(':visible')){
							screen.hide();
						}
					}
					mod.find('.close-reveal-modal').bind('click', function(){
						scope.closeModal();
					})
				}
			};
})
.directive("confirm", function() {
		return {
				restrict:"A",
				transclude:true,
				scope:{
					header:"@",
					body:"@",
					footer:"@",
					negativeText:"@",
					affirmativeText:"@",
					negativeAction:"&",
					affirmativeAction:"&",
					buttonClass:"@",
					buttonIcon:"@",
					disabled:"&",
					buttonFloat:"@",
					enforceDialog:"@"
				},
				link: function(scope, element, attrs) {
					// convert the green and red classes to the BRC classes save and cancel
					// this is just here for ease of use
					attrs.buttonClass = (RegExp(/green/i).test(attrs.buttonClass))? 'save':attrs.buttonClass;
					attrs.buttonClass = (RegExp(/red/i).test(attrs.buttonClass))? 'cancel':attrs.buttonClass;

					var mod = angular.element('#global-modal');
					var screen = angular.element('#global-modal-bg');
					element.bind('click', function(){
						scope.openModal();
						scope.$apply();
					});
					scope.openModal = function(){
						var defaults = {
									buttonClass:"",
									buttonIcon:"icon-checkmark",
									header:"Attention!",
									body:"Are you sure you want to continue?",
									footer:"",
									negativeText:"No",
									affirmativeText:"Yes",
									enforceDialog:true
						};
						// Apply default values if the attribute was not sent
						angular.forEach(defaults, function (value, key)	{
							mod.scope()[key] = (attrs[key] === undefined)? value:attrs[key];
							mod.scope().enforceDialog = (attrs.enforceDialog == "false")? false:true;
						});
						if(mod.scope().enforceDialog){
							screen.show();
							mod.show().css('visibility','visible');
							angular.element('#global-modal-bg').show();
						}else{
							//console.warn('bypass modal');
							scope.affirmativeAction();
						}
						mod.scope().negative = function(){
							scope.closeModal();
							scope.negativeAction();
						}
						mod.scope().affirmative = function(){
							scope.closeModal();
							scope.affirmativeAction();
						}
					}

					scope.closeModal = function(){
						mod.hide();
						if(!angular.element('#account-settings-modal').is(':visible')){
							screen.hide();
						}
					}
					mod.find('.close-reveal-modal').bind('click', function(){
						scope.closeModal();
					})
				}
			};
});



