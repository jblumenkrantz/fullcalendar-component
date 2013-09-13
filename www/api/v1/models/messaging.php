<?php

/**
* The messagingGroup class is used to handle all of the adminstrators
* group and emergency messaging group management
*/

class MessagingGroup extends PinwheelModelObject
{
	public $group_id;
	public $creator_id;
	public $group_name;
	public $org_id;

	/**
	*	The parent construct call static hook MessagingGroup::defaults to assign
	*	initial values to MessagingGroup properties.
	*/
	static protected function defaults () {
		return array (
			'group_id' => NULL,
			'creator_id' => NULL,
			'group_name' => NULL,
			'org_id' => NULL
		);
	}

	static public function factory ($p) {
		return new MessagingGroup($p);
	}

	static public function load($user_id, $pinsqli = NULL) {
		$groups = static:: genericQuery(
			"SELECT group_id, creator_id, group_name, org_id
				From messaging_groups
				WHERE creator_id = '$user_id'
			"
		, $pinsqli);
		return $groups;
	}

	static public function loadRole($user_id, $org_id){
		$resulti = static:: genericQuery(
			"SELECT
				user_role
			FROM
				organization_admins
			WHERE
				org_id = '$org_id'
			AND
				user_id = '$user_id'
			LIMIT 1
			"
		);
		
		return $resulti;
	}

	static public function loadGroup($group_id, $all_data=TRUE, $pinsqli = NULL) {
		$groups = static:: genericQuery(
			"SELECT group_id, creator_id, group_name, org_id
				From messaging_groups
				WHERE group_id = '$group_id'
			"
		, $pinsqli);
		if($all_data){
			foreach ($groups as $group) {
				$group->recipients = static:: loadUsers($group_id);
			}
		}
		return $groups;
	}

	static function createLogRecord($user_id, $message, $ip, $start_time,$pinsqli=NULL){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$batch_id = MySQLConnection:: generateUID('batch');
		$message = mysql_real_escape_string($message);
		$resulti = $pinsqli->query(
			"INSERT INTO messaging_master_log (
					batch_id,
					message_body,
					send_time,
					sender_id,
					sender_ip,
					message_status
				)
				Values (
					'$batch_id',
					'$message',
					FROM_UNIXTIME('$start_time'),
					'$user_id',
					'$ip',
					'sending'
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		return $batch_id;
	}

	static function updateLogRecord($array, $pinsqli=NULL){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		
		$array = array_map(array($pinsqli, 'real_escape_string'), $array);
		
		$batchID = $array['batch_id'];
		unset($array['batch_id']);  // you dont want to update this key in the database
		
		$string = null;
		foreach ($array as $key => $value) {
			$string .= $key.'='.$value.',';
		}
		$sql = "UPDATE messaging_master_log SET ".rtrim($string,',')." WHERE batch_id = '$batchID'";

		$resulti = $pinsqli->query("$sql");

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
	}

	static function loadUsers($group_id, $all_data=TRUE, $pinsqli=NULL) {
		$groupUsers = static:: genericQuery(
			"SELECT user_id
				From messaging_group_users
				WHERE group_id = '$group_id'
			"
		, $pinsqli);
		if(!$all_data){
			$groupUsersList = array();
			foreach ($groupUsers as $user) {
				array_push($groupUsersList, $user->user_id);
			}
			return $groupUsersList;
		}
		$userIds = array();
		foreach ($groupUsers as $key => $value) {
			array_push($userIds, $value->user_id);
		}
		$userIds = is_array($userIds)? "'".implode("','", $userIds)."'": "'$userIds'";
		$users = static:: genericQuery(
			"SELECT user_id, first_name, last_name, user_handle
				From users
				WHERE user_id IN ($userIds)
			"
		, $pinsqli);

		return $users;
	}

	static function loadUsersBatch($users_batch, $emergency='false'){
		$userIds = array();
		foreach ($users_batch as $userID) {
			array_push($userIds, $userID);
		}
		$userIds = is_array($userIds)? "'".implode("','", $userIds)."'": "'$userIds'";
		$contactPoints = static:: genericQuery(
			"SELECT 
    			case point_type
        			when 'mobile' then CONCAT(country_code,address,'-', carrier_id)
					when 'email' then address
    			end as address
			FROM contact_points 
			WHERE user_id IN ($userIds)
			AND activated_on IS NOT NULL 
			AND (emergency_only = '$emergency' or emergency_only = false)
			"
		);
		$recipientIDs = array();
		foreach ($contactPoints as $key => $value) {
			array_push($recipientIDs, $value->address);
		}
		return $recipientIDs;
	}

	static function loadOrgSubscribers($org_id){
		$userIds = static:: genericQuery(
			"SELECT 
				user_id
			FROM users_orgs 
			WHERE org_id = '$org_id'
			"
		);
		$recipientIDs = array();
		foreach ($userIds as $user) {
			array_push($recipientIDs, $user->user_id);
		}
		return $recipientIDs;
	}

	static function loadCalendarSubscribers($calendars){
		$recipientIDs = array();
		foreach ($calendars as $calendar_id) {
			$userIds = static:: genericQuery(
				"SELECT 
					user_id
				FROM calendar_subs 
				WHERE calendar_id = '$calendar_id'
				"
			);
		
			foreach ($userIds as $user) {
				array_push($recipientIDs, $user->user_id);
			}
		}
		return $recipientIDs;
	}

	static function loadAdmins($user_id, $pinsqli=NULL){
		$org = static:: genericQuery(
			"SELECT org_id
				From organization_admins
				WHERE user_id = '$user_id'
			"
		, $pinsqli);
		$org = array_shift($org); // get the first org_id returned
		$adminIds = static:: genericQuery(
			"SELECT user_id
				From users_orgs
				WHERE org_id = '{$org->org_id}'
			"
		, $pinsqli);

		$subscriberIds = array();
		foreach ($adminIds as $key => $value) {
			array_push($subscriberIds, $value->user_id);
		}
		$subscriberIds = is_array($subscriberIds)? "'".implode("','", $subscriberIds)."'": "'$subscriberIds'";
		
		$admins = static:: genericQuery(
			"SELECT first_name, last_name, user_handle, user_id
				From users
				WHERE user_id IN ($subscriberIds)
					AND active = 1
			"
		, $pinsqli);

		return $admins;
	}

	static function getPrimaryOrg($user_id, $pinsqli=NULL) {
		$org = static:: genericQuery(
			"SELECT org_id, user_role
				From organization_admins
				WHERE user_id = '$user_id'
			"
		, $pinsqli);
		if($org == null){
			$org = static:: genericQuery(
				"SELECT org_id
					From users_orgs
					WHERE user_id = '$user_id'
					LIMIT 1
				"
			, $pinsqli);
			$org[0]->user_role = 'user';
		}
		return $org;
	}

	static function loadMessageLog($user_id) {
		$messageLog = static:: genericQuery(
			"SELECT batch_id, sender_ip, message_status, message_body, send_time, complete_time, email_attempted, sms_attempted, email_failed, sms_failed
				From messaging_master_log
				WHERE sender_id = '$user_id'
			"
		);
		return $messageLog;
	}

	static function loadMessageById($batch_id) {
		$message = static:: genericQuery(
			"SELECT batch_id, sender_ip, message_status, message_body, send_time, complete_time, email_attempted, sms_attempted, email_failed, sms_failed
				From messaging_master_log
				WHERE batch_id = '$batch_id'
			"
		);
		return $message;
	}

	static function getMessageStatus($batch_id) {
		$masterStatus = static:: genericQuery(
			"SELECT message_status
				From messaging_master_log
				WHERE batch_id = '$batch_id'
			"
		);
		$sql = "SELECT
				(SELECT count(batch_id) FROM messaging_detail_log WHERE batch_id = '$batch_id' AND (message_status = 'success' OR message_status = 'failed'))
				/ count(batch_id)*100 AS percent_complete
				FROM messaging_detail_log
				WHERE batch_id = '$batch_id'
			";
		$detailStatus = static:: genericQuery($sql);
		return array('percent_complete'=>round(array_shift($detailStatus)->percent_complete),'message_status'=>array_shift($masterStatus)->message_status);
	}

	static function verifyGroupOrgs($groups, $org_id, $pinsqli=NULL){
		$groupIds = is_array($groups)? "'".implode("','", $groups)."'": "'$groups'";
		$validOrgs = static:: genericQuery(
			"SELECT group_id
				From messaging_groups
				WHERE org_id = '$org_id'
				AND group_id IN ($groupIds)
			"
		, $pinsqli);
		return (count($validOrgs) == count($groups))? false:true;
	}

	static function createGroup($user_id, $group_name, $recipients, $pinsqli=NULL) {
		if (is_object($group_name))
			$group_name = get_object_vars($group_name);
		if (is_object($recipients))
			$recipients = get_object_vars($recipients);

		$pinsqli = DistributedMySQLConnection:: writeInstance();
		
		$group_name = array_shift(array_map(array($pinsqli, 'real_escape_string'), $group_name));
		$recipients = array_map(array($pinsqli, 'real_escape_string'), $recipients);
		
		$group_id = MySQLConnection:: generateUID('group');
		
		$org = array_shift(MessagingGroup::getPrimaryOrg($user_id));

		$resulti = $pinsqli->query(
			"INSERT INTO messaging_groups (
					group_id,
					creator_id,
					group_name,
					org_id
				)
				Values (
					'$group_id',
					'$user_id',
					'{$group_name}',
					'{$org->org_id}'
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		foreach ($recipients as $recipient) {
			$resulti = $pinsqli->query(
				"INSERT INTO messaging_group_users (
						group_id,
						user_id
					)
					Values (
						'$group_id',
						'$recipient'
					)
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		}
		return static:: loadGroup($group_id);
	}

	static function updateGroup($user_id, $group, $recipients, $pinsqli=NULL) {	
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		
		$group = array_map(array($pinsqli, 'real_escape_string'), $group);
		$recipients = array_map(array($pinsqli, 'real_escape_string'), $recipients);		
		
		$resulti = $pinsqli->query(
			"UPDATE messaging_groups 
				SET
					group_name = '{$group['group_name']}'
				WHERE
					group_id = '{$group['group_id']}'
				AND
					org_id = '{$group['org_id']}'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		$oldRecipients = static:: loadUsers($group['group_id'], FALSE);

		$deleteRecipients = array_diff($oldRecipients, $recipients);
		$addRecipients = array_diff($recipients, $oldRecipients);
		
		foreach ($addRecipients as $recipient) {
			$resulti = $pinsqli->query(
				"INSERT INTO messaging_group_users (
						group_id,
						user_id
					)
					Values (
						'{$group['group_id']}',
						'$recipient'
					)
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		}
		foreach ($deleteRecipients as $recipient) {
			$resulti = $pinsqli->query(
				"DELETE
					FROM messaging_group_users 
					WHERE
						group_id = '{$group['group_id']}'
					AND
						user_id = '$recipient'
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		}
		return static:: loadGroup($group['group_id']);
	}

	static function deleteGroup($user_id, $group, $pinsqli=NULL) {	
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		
		$group = array_map(array($pinsqli, 'real_escape_string'), $group);
		$group = array_shift(static:: loadGroup($group['group_id'], FALSE));

		// test if the person deleteing the group is the creator
		if($group->creator_id != $user_id){
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
		$resulti = $pinsqli->query(
			"DELETE 
				FROM
					messaging_groups
				WHERE
					group_id = '{$group->group_id}'
				AND
					creator_id = '{$group->creator_id}'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		$resulti = $pinsqli->query(
			"DELETE 
				FROM
					messaging_group_users
				WHERE
					group_id = '{$group->group_id}'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		return $group;
	}
}