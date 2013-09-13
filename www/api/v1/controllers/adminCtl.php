<?php

class AdminCtl
{
	function getAdminData () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$primaryOrg = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primaryOrg->user_role;

		if (!(preg_match("/admin/i", $userRole)) ){
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}

		$admin = new Admin($primaryOrg);
		$admin->loadPublicCalendarsByUser($authUserID);
		$admin->loadPrivateCalendarsByUser($authUserID);

		if ((preg_match("/super-admin/i", $userRole)) ){
			$admin->loadAdmins();
			$admin->loadSubscribers();
		}
		
		echo json_encode($admin);
	}

	function promoteCalendar() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$primaryOrg = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primaryOrg->user_role;
		if (!(preg_match("/admin/i", $userRole)) ){
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		if(Admin::testCalendarOwnership($properties->calendar_id,$authUserID)){
			$calendar = Admin::promoteToPublicCalendar($properties, $primaryOrg->org_id);
			echo json_encode($properties);
		}
	}

	function demoteCalendar() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$primaryOrg = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primaryOrg->user_role;

		if (!(preg_match("/admin/i", $userRole)) ){
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		if(Admin::testCalendarOwnership($properties->calendar_id,$authUserID)){
			Admin::demoteFromPublicCalendar($properties, $primaryOrg->org_id);
			echo json_encode($properties);
		}
	}

	function addAdminToOrg() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$primaryOrg = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primaryOrg->user_role;

		if ((preg_match("/super-admin/i", $userRole)) ){
			$tenativeAdmin = Admin::loadAdmin($properties);
			error_log(print_r($tenativeAdmin,true));
			if((count($tenativeAdmin) == 0) && ($tenativeAdmin[0]->user_role != "super-admin")){
				Admin::addAdminToOrg($properties);
			}
			else{
				$ExistingAdminError = new AdminExistsException();
				echo $ExistingAdminError->json_encode();
				exit;
			}
		}
		else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		echo json_encode(array_shift(Admin::loadAdmin($properties)));
	}

	function removeAdminFromOrg() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$primaryOrg = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primaryOrg->user_role;
		if ((preg_match("/super-admin/i", $userRole)) ){
			Admin::removeAdminFromOrg($properties);
		}
		else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		echo json_encode($properties);
	}

	function addAdminToCal() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		if(Admin::testCalendarOwnership($properties->calendar_id,$authUserID)){
			Admin::addAdminToCal($properties);
		}
		else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		echo json_encode($properties);
	}

	function removeAdminFromCal() {
		$properties = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		if(Admin::testCalendarOwnership($properties->calendar_id,$authUserID)){
			Admin::removeAdminFromCal($properties);
		}
		else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		echo json_encode($properties);
	}

}
