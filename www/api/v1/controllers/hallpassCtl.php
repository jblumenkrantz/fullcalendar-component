<?php

class HallpassCtl
{
	function getActivePasses($id = null) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$hallpasses = Hallpass::loadActive();
		$hallpass->in_time = (!$hallpass->in_time)? null:$hallpass->in_time;
		echo json_encode($hallpasses);
	}
	function getAllPasses($id = null) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$hallpasses = Hallpass::loadAll();
		foreach ($hallpasses as $key => $hallpass) {
			$hallpass->in_time = (!$hallpass->in_time)? null:$hallpass->in_time;
		}
		echo json_encode($hallpasses);
	}

	function checkInPass ($id = null) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$pass = array_shift(Hallpass::load($id));
		$hallpass = array_shift(Hallpass::checkInPass($pass, $authUserID));
		$hallpass->in_time = (!$hallpass->in_time)? null:$hallpass->in_time;
		echo json_encode($hallpass);
	}

	function createHallpass(){
		$authUserID = Authorize:: sharedInstance()->userID();
		$hallpass = array_shift(Hallpass:: create(json_decode(Request:: body())));
		echo json_encode($hallpass);
	}
	function getUserList($id){
		echo json_encode(Hallpass::loadOrgUsers($id));
	}

}
