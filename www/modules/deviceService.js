'use strict';

angular.module('pinwheelApp').factory('DeviceService', function() {
	var ds = {
		isTouch: !!('ontouchstart' in window) || !!('onmsgesturechange' in window),
		isIpad: (navigator.userAgent.match(/iPad/i) != null),
		isIphone: (navigator.userAgent.match(/iPhone/i) != null),
		isIpod: (navigator.userAgent.match(/iPod/i) != null),
		isAndroid: (navigator.userAgent.match(/Android/i) != null),
		describeDevice: function(returnAsString) {
			var arr = [];
			//if touch device
			if (this.isTouch) {
				arr.push("touchpad");
			}
			//iOS iPad
			if (this.isIpad) {
				arr.push("iPad", "iOS");
			}
			//iOS iPhone
			if (this.isIphone) {
				arr.push("iPhone", "iOS");
			}
			//iOS iPod
			if (this.isIpod) {
				arr.push("iPod", "iOS");
			}
			//Android
			if (this.isAndroid) {
				arr.push("Android");
			}
			return (returnAsString) ? arr.join(" ") : arr;
		}
	}	

	ds.isIOS = (ds.isIpad || ds.isIphone || ds.isIpod || navigator.userAgent.match(/crios/i) != null);
	
	return ds;
});