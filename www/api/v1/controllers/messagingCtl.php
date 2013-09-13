<?php

class MessagingCtl
{
	function getMessagingGroups () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$groups = MessagingGroup:: load($authUserID);
			foreach ($groups as $group) {
				$group->recipients = MessagingGroup:: loadUsers($group->group_id);
				$groupList->groups[$group->group_id] = $group;
			}
			$groupList->subscribers = MessagingGroup:: loadAdmins($authUserID);
			$groupList->primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
			$groupList->message_log = MessagingGroup:: loadMessageLog($authUserID);
			echo json_encode($groupList);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}

	function getMessageByID () {
		$body = json_decode(Request:: body());
		//error_log(print_r($body->batch_id,true));
		$authUserID = Authorize:: sharedInstance()->userID();
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$message = array_shift(MessagingGroup:: loadMessageById($body->batch_id));
			echo json_encode($message);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}

	function sendMessage () {
		$body = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$userRole = array_shift(MessagingGroup:: loadRole($authUserID, $body->org_id))->user_role;
		$body->send_time = time();

		// validate message Authenticity by comparing group_id and org_id
		// this will prevent someone from spoofing the system and sending messages as a
		// different orginization
		if(MessagingGroup:: verifyGroupOrgs($body->groups, $body->org_id)){
			$spoofError = new SpoofProtectionException();
			echo $spoofError->json_encode();
			exit;
		}

		if (preg_match("/admin/i", $userRole)) {
			if (!(preg_match("/super-admin/i", $userRole)) && $body->emergency){
				$PriviledgesError = new InsuficientEmergencyMessagingPriviledgesException();
				echo $PriviledgesError->json_encode();
				exit;
			}
			// Strip out duplicate recipients
			$body->recipients = array_merge(array_unique((array)PinwheelModelObject:: object_to_array($body->recipients)), array());

			$body->recipients = ($body->emergency)? array_unique(MessagingGroup::loadOrgSubscribers($body->org_id)):$body->recipients;
			$body->recipients = array_unique(array_merge(MessagingGroup:: loadCalendarSubscribers($body->calendars),$body->recipients));

			$recipients = array_unique(MessagingGroup:: loadUsersBatch($body->recipients, $body->emergency));
			if(count($recipients) > 0){
				$body->batch_id = MessagingGroup:: createLogRecord($authUserID,$body->body,$_SERVER['REMOTE_ADDR'],$body->send_time);
			}
			else{
				$noMessageRecipientsError = new NoMessageRecipientsException();
				echo $noMessageRecipientsError->json_encode();
				exit;
			}
			$processingTime = time() - $body->send_time;
			MessagingGroup:: updateLogRecord(array('batch_id'=>$body->batch_id,'building_recipients_seconds'=>$processingTime));
			$recipientString = is_array($recipients)? implode(",", $recipients): $recipients;

			$subject = ($body->emergency)? "EMERGENCY ALERT!":"New Notification";
			$postAuth = sha1($recipients[0]."rainbowkitties");
			//error_log(print_r($postAuth,true));

			$fields = array(
									'BatchId' => $body->batch_id,
									'Destination' => $recipientString,
									'Subject' => $subject,
									'PlainBody' => $body->body,
									'HtmlBody' => $body->body,
									'HandoffTime' => $body->send_time+$processingTime,
									'Auth' => $postAuth
							);

			$data = "";
			foreach( $fields as $key => $value ) $data .= "$key=" . urlencode( $value ) . "&";
			//error_log(print_r($data.' - '.$body->send_time,true));

			// run transaction
			$ch = curl_init("https://messenger-brc.sdicgdev.com"); 
			//error_log(print_r($ch,true));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, 1); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
			curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $data, "& " )); // use HTTP POST to send form data

			$result = curl_exec($ch);
			if(curl_errno($ch))
			{
				// TODO: create exception for message send failure
			    error_log(print_r(curl_error($ch),true));
			}
			
			//close connection
			curl_close($ch);
			
			echo json_encode(array_shift(MessagingGroup:: loadMessageById($body->batch_id)));
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}

	function getMessageStatus() {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$batchStatus = MessagingGroup:: getMessageStatus($body->batch_id);
			echo json_encode($batchStatus);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}

	function getAdmins() {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$subscribers = MessagingGroup:: loadAdmins($authUserID);
			echo json_encode($subscribers);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}
	function createMessagingGroup () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$groupMembers = MessagingGroup:: createGroup($authUserID, array($body->group_name), $body->recipients);
			echo json_encode($groupMembers);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}
	function updateMessagingGroup () {
		$body = json_decode(Request:: body());
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$primary_org = array_shift(MessagingGroup:: getPrimaryOrg($authUserID));
		$userRole = $primary_org->user_role;
		if (preg_match("/admin/i", $userRole)) {
			$recipients = $body->recipients;
			$body = PinwheelModelObject:: object_to_array($body);
			unset($body['recipients']);
			$group = MessagingGroup:: updateGroup($authUserID, $body, $recipients);
			echo json_encode($group);
		}else{
			$PriviledgesError = new InsuficientPriviledgesException();
			echo $PriviledgesError->json_encode();
			exit;
		}
	}
	function deleteMessagingGroup () {
		$body = json_decode(Request:: body());
		$body = PinwheelModelObject:: object_to_array($body);
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		//TODO: validate user and whatnot before deleting group
		$group = MessagingGroup:: deleteGroup($authUserID,$body);
		$group->delete_success = true;
		echo json_encode($group);
	}

}