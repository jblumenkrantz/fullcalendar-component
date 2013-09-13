<?php
class CalendarCtl
{


	/**
	*	CalendarCtl::get provides an interface for aquiring calendars by means of
	*	the calendar-id(s) passed to the method.
	*
	*	@param $id  String of calendar-ids seperated by '/'
	*	@return JSON encode array of Calendar(s)
	*
	*	@todo HTTP CONDITIONAL GET
	*/
	function get($id) {
		$calendars = Calendar::loadActive(split('/', $id));
	 	echo json_encode($calendars);
	}

	/**
	* 	CalendarCtl::getAll provides an interface for aquiring all calendars
	* 
	*	@return JSON encode array of Calendar(s)
	*
	* 	@todo get all calendars per signed in user
	*/
	function getAll ($id = null) {
		$authUserID = null;
		$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		if ($id !== null &&  $authUserID != $id) {
			$forbidden = new UserForbiddenException($authUserID);
			echo $forbidden->json_encode();
			exit;
		}
		
		$calendars = Calendar::loadIndex($id === null? $authUserID: $id);
	 	echo json_encode($calendars);
	}
	
	/**
	*	CalendarCtl::Create provides an interface for createing new calendars. The
	*	properties of the new Calendar(s) are passed into the session via the Request
	*	Body in the form of JSON.
	*
	*	@return Returns newly created JSON Calendar(s).
	*
	*	CalendarCtl::create's query pattern is not atomic due to an INSERT followed
	*	by a SELECT without locking. There does exist a posibility,
	*	however unlikely, after the INSERT an update occurs before the following
	*	SELECT on the newly created Calendar(s), and if the calendar_id and
	*	last_modified where the only properties to be returned, the client's
	*	cached/local view of the Calendar(s) would be improperly bound to the wrong
	*	last_modified(s). As a result, the improperly bound last_modified(s) could
	*	lead to a false positives when performing future validations (CONDITIONAL GET),
	*	aka. lost of synchrony. To handle the lost of synchrony, CalendarCtl::create
	*	returns full Calendar(s) to the client on return.
	*/
	function create($id = null) {
		$authUserID = null;
		$authUserID = Authorize:: sharedInstance()->userID();
		if ($id !== null &&  $authUserID != $id) {
			$forbidden = new UserForbiddenException($authUserID);
			echo $forbidden->json_encode();
			exit;
		}
		$calendars = Calendar:: create($id === null? $authUserID: $id,json_decode(Request:: body()));
		foreach($calendars as $calendar){
			$calendar->subscribed = true;
		}
		echo json_encode($calendars);
		User:: incrementVersion($authUserID);
	}

	/**
	*	CalendarCtrl::update provides an interface for updating pre-existing calendars.
	*	The properties of the Calendar(s) to update are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*
	*	@return Returns updated JSON Calendar(s) or, in the case of update conflict(s),
	*	JSON Calendar Conflict(s) of the structure:
	*		{
	*			'errno'    :409,
	*			'message'  :...,
	*			'resource' :{<calendar>},
	*			'conflicts':[{<calendar1>}...{calendarT}]}.
	*		}.
	*
	*
	*	Client sends full Calendar(s) to CalendarCtrl::update interfce. If update conflict(s) occur,
	*	CalendarCtrl::update will return the Calendar(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless of local client changes made to
	*	the same Calendar in the interim of request.
	*/
	function update() {
		$authUserID = Authorize:: sharedInstance()->userID();
		//error_log("---- calendar request body ----");
		//error_log(Request:: body());
		$tsprops = json_decode(Request:: body());
		echo '[';
		if (is_object($tsprops))
			$tsprops = array($tsprops);
		$ncalendars = count($tsprops);
		foreach ($tsprops as $tsprop) {
			try {
				//grab calendar obj and version
				$calendar = new Calendar($tsprop);
				error_log(print_r($calendar,true));
				//grab shared sql conn
				$pinsqli = DistributedMySQLConnection:: writeInstance();

				//perform calendar updates
				$calendar->update_subscriptions($pinsqli);
				$calendar->update_reminders($pinsqli);
				$calendar->update($pinsqli);
				if($calendar->creator_id == $authUserID){
					if(property_exists($tsprop,'public') && $tsprop->public){
						Admin::promoteToPublicCalendar($tsprop);
					}
					else{
						Admin::demoteFromPublicCalendar($tsprop);
					}
					error_log(print_r('calendar updated',true));
				}

				error_log(print_r($calendar,true));

				//return response
				echo json_encode(array($calendar->calendar_id => $calendar));
				
			} catch (CalendarDataConflictException $e) {
				echo $e->json_encode();
			} catch (CalendarDoesNotExist $e) {
				echo $e->json_encode();
			}
			if (--$ncalendars > 0) echo ',';
		}
		echo ']';
		User:: incrementVersion($authUserID);
	}

	function unsubscribe() {
		$subscription = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		Event::removeAdhocEvents($subscription, $authUserID);
		echo(json_encode(Calendar::unsubscribe($subscription, $authUserID)));
		User:: incrementVersion($authUserID);
	}

	function subscribe($body=NULL, $return=FALSE) {
		$subscription = ($body==NULL)? json_decode(Request:: body()):$body;
		//error_log(print_r($subscription,true));
		$authUserID = Authorize:: sharedInstance()->userID();
																		
		$subscription = json_encode(array('tasks'=> Task::getBatch(array("tasks.calendar_id='{$subscription->calendar_id}'","(tasks.creator_id='$authUserID' OR tasks.creator_id=(SELECT creator_id from calendars where calendar_id = '{$subscription->calendar_id}'))")),
		                       'events'=> Event::getBatch(array("events.calendar_id='{$subscription->calendar_id}'","(events.creator_id='$authUserID' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$subscription->calendar_id}'))")),
		                       'subscription'=>Calendar::subscribe($subscription, $authUserID)));
		if($return){
			User:: incrementVersion($authUserID);
			return json_decode($subscription);
		}else{
			echo $subscription;
		}
	}
	function updateVewSettings() {
		$calendar = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();

		Calendar::updateSubscription($calendar->calendar_id, $calendar->viewing, $authUserID);
		echo json_encode($calendar);
		User:: incrementVersion($authUserID);
	}
	/**
	*	CalendarCtrl::delete provides an interface for deleting pre-existing calendars.
	*	The properties of the Calendar(s) to delete are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*	@return Returns '{}' or, in the case of delete conflicts(s), JSON Calendar
	*	Conflict(s) (see above).
	*
	*	Client sends full Calendar(s) to CalendarCtrl::delete interfce. If delete conflict(s) occur,
	*	CalendarCtrl::delete will return the Calendar(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless the local delte of the Same Calendar.	
	*/
	function delete(){
		$tsprops = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		echo '[';
		if (is_object($tsprops))
			$tsprops = array($tsprops);
		$ncalendars = count($tsprops);
		foreach ($tsprops as $tsprop) {
			try {
				$calendar = new Calendar($tsprop);
				$calendar->delete();
				echo json_encode($calendar);
			} catch (CalendarDataConflictException $e) {
				echo $e->json_encode();
			} catch (CalendarDoesNotExist $e) {
				echo $e->json_encode();
			}
			if (--$ncalendars > 0) echo ',';
		}
		echo ']';
		User:: incrementVersion($authUserID);
	}
}
?>

