<?php

class SharingCtl
{
	function share () {
		$shareProps = json_decode(Request:: body());
		$share = new Sharing($shareProps);
		$share->convertRecipients();
		$share->compileShareRequest();
		echo json_encode(array('success'=>true));
	}
	function getInvitationInfo() {
		$shareProps = json_decode(Request::body());
		$shareProps->auth_user_id = Authorize:: sharedInstance()->userID();
		$shareDetails = Sharing::invitationDetails($shareProps);
		echo json_encode($shareDetails);
	}
	function handleInvitation() {
		$shareProps = json_decode(Request:: body());
		//$authUserID = Authorize:: sharedInstance()->userID();
		$shareProps->auth_user_id = Authorize:: sharedInstance()->userID();
		if($shareProps->element_id = Sharing::validteToken($shareProps,$shareProps->auth_user_id)){
			$elementType = explode('_', $shareProps->element_id)[0];
			$body = (object) array('user_id'=>$shareProps->auth_user_id,$elementType.'_id'=>$shareProps->element_id, 'color'=>'#f00');
			if($elementType == 'event'){
				$subscription = EventCtl::subscribe($body,true);
			}
			else{
				if(!Sharing::alreadySubscribedCalendar($body)){
					$subscription = CalendarCtl::subscribe($body,true);
				}
				elseif(Sharing::isAdhocSubscription($body)){
					$subscription = Sharing::promoteSubscription($body);
					// Clean up any indvidual adhoc events that the user might have
					// to prevent them from becoming orphaned yo.
					Event::removeAdhocEvents($body, $shareProps->auth_user_id);
				}
				else {
					$subscription = json_decode(json_encode(array('tasks'=> Task::getBatch(array("tasks.calendar_id='{$shareProps->element_id}'","(tasks.creator_id='$shareProps->auth_user_id' OR tasks.creator_id=(SELECT creator_id from calendars where calendar_id = '{$shareProps->element_id}'))")),
			                       'events'=> Event::getBatch(array("events.calendar_id='{$shareProps->element_id}'","(events.creator_id='$shareProps->auth_user_id' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$shareProps->element_id}'))")),
			                       'subscription'=>array_shift(Calendar::load($shareProps->element_id)))));
				}
				$color = $subscription->subscription->color;
				unset($subscription->subscription);
				
				$calendar = (object) array_merge((array) array_shift(Calendar::load($shareProps->element_id)), array('color'=>$color));
				$subscription = (object) array_merge((array) $subscription, array('calendars'=>array($calendar->calendar_id => $calendar)));
			}
		}else{
			$badToken = new InvalidShareTokenException();
			echo $badToken->json_encode();
			exit;
		}
		Sharing::updateShareRequest($shareProps);
		echo json_encode($subscription);
	}
}