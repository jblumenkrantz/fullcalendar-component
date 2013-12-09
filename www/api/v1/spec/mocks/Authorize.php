<?php
	class Authorize {
		static protected $sharedInstance = NULL;
		static function sharedInstance () {
			return new Authorize();
		}

		public function userID () {}
	}
?>
