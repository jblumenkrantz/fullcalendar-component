<?php

class FacilitiesCtl
{
	function getLocations($id = null) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$locations = Facilities::load('org_1');
		echo json_encode($locations);
	}
}
