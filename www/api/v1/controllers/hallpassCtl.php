<?php

class HallpassCtl
{
	function getActivePasses($id = null) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$hallpasses = Hallpass::loadActive();
		echo json_encode($hallpasses);
	}

	function getData () {
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		//error_log($authUserID);
		$user = User:: getData($authUserID);
		echo json_encode($user);
	}

	function loadNewUserOptions () {
		$options = User::loadNewUserOptions();
		echo json_encode($options);
	}
}
