<?php

/**
* The Admin class is used to handle all of the Administrator and Super-Administrator actions
*/

class Admin extends PinwheelModelObject
{
	public $org_id;
	public $admins;
	public $public_calendars;
	public $private_calendars;
	public $subscribers;

	/**
	*	The parent construct call static hook Admin::defaults to assign
	*	initial values to admin properties.
	*/
	static protected function defaults () {
		return array (
			'user_id' => NULL,
			'admins'  => NULL,
			'public_calendars' => NULL,
			'private_calendars' => NULL,
			'subscribers' => NULL
		);
	}

	static public function factory ($p) {
		return new Admin($p);
	}

	public function loadAdmins(){
		
		$admins = static:: genericQuery(
			"SELECT 
    			organization_admins.user_id, organization_admins.user_role,first_name,last_name,user_handle
				FROM organization_admins
				LEFT OUTER JOIN users
				ON users.user_id = organization_admins.user_id
				WHERE org_id = '{$this->org_id}'
				AND users.active = true
			"
		);
		$this->admins = $admins;
		return $admins;
	}

	public function loadAdmin($user){
		$admin = PinwheelModelObject:: genericQuery(
			"SELECT 
    			organization_admins.user_id, organization_admins.user_role,first_name,last_name,user_handle
				FROM organization_admins
				LEFT OUTER JOIN users
				ON users.user_id = organization_admins.user_id
				WHERE users.user_id = '{$user->user_id}'
				AND org_id = '{$user->org_id}'
				AND users.active = true
			"
		);
		return $admin;
	}

	public function loadSubscribers() {
		$userIds = "'".implode("','", MessagingGroup:: loadOrgSubscribers($this->org_id))."'";
		$admins = static:: genericQuery(
			"SELECT first_name, last_name, user_handle, user_id
				From users
				WHERE user_id IN ($userIds)
			"
		);
		$this->subscribers = $admins;
	}

	public function loadOrgCalendars(){
		
		$calendars = static:: genericQuery(
			"SELECT 
    			public_calendars.calendar_id, calendar_name
				FROM public_calendars
				LEFT OUTER JOIN calendars
				ON calendars.calendar_id = public_calendars.calendar_id
				WHERE org_id = '{$this->org_id}'
			"
		);
		$this->public_calendars = $calendars;
		return $calendars;
	}

	public function loadPublicCalendarsByUser($user_id){
		$calendars = static:: genericQuery(
			"SELECT 
    			public_calendars.calendar_id, calendar_name, calendar_subs.color
				FROM public_calendars
				LEFT OUTER JOIN calendars
				ON calendars.calendar_id = public_calendars.calendar_id
				LEFT OUTER JOIN calendar_subs
				ON calendars.calendar_id = calendar_subs.calendar_id AND calendars.creator_id = calendar_subs.user_id
				WHERE org_id = '{$this->org_id}'
				AND creator_id = '$user_id'
			"
		);
		$calray = array();
		foreach ($calendars as $calendar) {
			$admins = static:: genericQuery(
				"SELECT 
	    			calendar_admins.user_id, users.first_name, users.last_name, users.user_handle
					FROM calendar_admins
					LEFT OUTER JOIN users
					ON calendar_admins.user_id = users.user_id
					WHERE calendar_id = '{$calendar->calendar_id}'
				"
			);
			$calendar = array_merge((array)$calendar, array('admins'=>$admins));
			array_push($calray, $calendar);
		}
		$this->public_calendars = $calray;
		return $calray;
	}

	public function loadPrivateCalendarsByUser($user_id){
		$calendars = static:: genericQuery(
			"SELECT 
				calendars.calendar_id, calendars.calendar_name, calendar_subs.color
				From calendars
				LEFT OUTER JOIN calendar_subs
				ON calendars.calendar_id = calendar_subs.calendar_id AND calendars.creator_id = calendar_subs.user_id
				where calendars.creator_id = '$user_id'
				and calendars.calendar_id NOT IN (
					SELECT 
    					public_calendars.calendar_id
					FROM public_calendars
					LEFT OUTER JOIN calendars
					ON calendars.calendar_id = public_calendars.calendar_id
					WHERE creator_id = '$user_id'
				)
			"
		);
		$calray = array();
		foreach ($calendars as $calendar) {
			$admins = static:: genericQuery(
				"SELECT 
	    			calendar_admins.user_id, users.first_name, users.last_name, users.user_handle
					FROM calendar_admins
					LEFT OUTER JOIN users
					ON calendar_admins.user_id = users.user_id
					WHERE calendar_id = '{$calendar->calendar_id}'
				"
			);
			$calendar = array_merge((array)$calendar, array('admins'=>$admins));
			array_push($calray, $calendar);
		}
		$this->private_calendars = $calray;
		return $calray;
	}

	public function testCalendarOwnership($cal_id, $user_id){
		$calendar = PinwheelModelObject:: genericQuery(
			"SELECT 
    			count(calendar_id) as total
				FROM calendars
				WHERE calendar_id = '$cal_id'
				AND creator_id = '$user_id'
			"
		);
		return ($calendar >= 1)? true:false;
	}

	public function promoteToPublicCalendar($p, $org_id=NULL){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p->org_id = (property_exists($p, 'org_id'))? $p->org_id:$org_id;
		//error_log(print_r($p,true));
		$result = $pinsqli->query(
			"INSERT IGNORE INTO public_calendars (
						org_id,
						calendar_id
					)
					Values (
						'{$p->org_id}',
						'{$p->calendar_id}'
					)
				"
		);
		if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

	public function demoteFromPublicCalendar($p, $org_id=NULL){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p->org_id = (property_exists($p, 'org_id'))? $p->org_id:$org_id;
		$resulti = $pinsqli->query("DELETE
				FROM public_calendars
				WHERE org_id = '{$p->org_id}'
				AND calendar_id = '{$p->calendar_id}'
			");
		
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		return $resulti;
	}

	function addAdminToOrg($p) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$result = $pinsqli->query(
			"INSERT INTO organization_admins (
						org_id,
						user_id,
						user_role
					)
					Values (
						'{$p->org_id}',
						'{$p->user_id}',
						'{$p->role}'
					)
				"
		);
		if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

	function removeAdminFromOrg($p) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$result = $pinsqli->query(
			"DELETE from organization_admins 
				WHERE org_id = '{$p->org_id}'
				AND user_id = '{$p->user_id}'
			"
		);
		if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		return $p;
	}

	function addAdminToCal($p) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$result = $pinsqli->query(
			"INSERT INTO calendar_admins (
						user_id,
						calendar_id
					)
					Values (
						'{$p->subscriber}',
						'{$p->calendar_id}'
					)
				"
		);
		if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

	function removeAdminFromCal($p) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$result = $pinsqli->query(
			"DELETE from calendar_admins 
				WHERE calendar_id = '{$p->calendar_id}'
				AND user_id = '{$p->subscriber}'
			"
		);
		if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

}