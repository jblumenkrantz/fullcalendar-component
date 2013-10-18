<?php

class ContactCtl
{
	function getContactPoints () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$contactPoints = Contact:: getContactPoints($authUserID);
		echo json_encode($contactPoints);
	}
	function createContactPoint () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$contactPoint = Contact:: createContactPoint($authUserID,$body);
		echo json_encode($contactPoint);
	}
	function updateContactPoint () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$contactPoint = Contact:: updateContactPoint($authUserID,$body);
		echo json_encode($contactPoint);
	}
	function deleteContactPoint () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$contactPoint = Contact:: deleteContactPoint($authUserID,$body);
		echo json_encode($contactPoint);
	}

	/* CONTACT POINT ACTIVATION FUNCITONS */
	function activateContactPoint () {
		$body = json_decode(Request:: body());
		error_log(print_r($body,true));
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$activationStatus = Contact:: activateContactPoint($authUserID,$body);
		echo json_encode($activationStatus);
	}
}