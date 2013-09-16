<?php
class EventCtl
{


	function getAll(){
		$authUserID = Authorize:: sharedInstance()->userID();
		$events = Event:: getUsersEvents($authUserID);
	 	echo json_encode($events);
	}
	/**
	*	EventCtl::get provides an interface for aquiring events by means of
	*	the event-id(s) passed to the method.
	*
	*	@param $id  String of event-ids seperated by '/'
	*	@return JSON encode array of Event(s)
	*
	*	@todo HTTP CONDITIONAL GET
	*/
	function get($id) {
		$events = Event::getBatch();
	 	echo json_encode($events);
	}


	/**
	*	EventCtl::Create provides an interface for createing new events. The
	*	properties of the new Event(s) are passed into the session via the Request
	*	Body in the form of JSON.
	*
	*	@return Returns newly created JSON Event(s).
	*
	*	EventCtl::create's query pattern is not atomic due to an INSERT followed
	*	by a SELECT without locking. There does exist a posibility,
	*	however unlikely, after the INSERT an update occurs before the following
	*	SELECT on the newly created Event(s), and if the event_id and
	*	last_modified where the only properties to be returned, the client's
	*	cached/local view of the Event(s) would be improperly bound to the wrong
	*	last_modified(s). As a result, the improperly bound last_modified(s) could
	*	lead to a false positives when performing future validations (CONDITIONAL GET),
	*	aka. lost of synchrony. To handle the lost of synchrony, EventCtl::create
	*	returns full Event(s) to the client on return.
	*/
	function create() {
		//create a single connection
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$events = Event:: create(json_decode(Request:: body()), $pinsqli);
		$authUserID = Authorize:: sharedInstance()->userID();
		//error_log(print_r(json_decode(Request:: body()),true));
		echo json_encode($events);
		User:: incrementVersion($authUserID);
	}

	/**
	*	EventCtrl::update provides an interface for updating pre-existing events.
	*	The properties of the Event(s) to update are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*
	*	@return Returns updated JSON Event(s) or, in the case of update conflict(s),
	*	JSON Event Conflict(s) of the structure:
	*		{
	*			'errno'    :409,
	*			'message'  :...,
	*			'resource' :{<event>},
	*			'conflicts':[{<event1>}...{eventE}]}.
	*		}.
	*
	*
	*	Client sends full Event(s) to EventCtrl::update interfce. If update conflict(s) occur,
	*	EventCtrl::update will return the Event(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless of local client changes made to
	*	the same Event in the interim of request.
	*/
	function update() {
		$evprops = json_decode(Request:: body());
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$authUserID = Authorize:: sharedInstance()->userID();
		echo '[';
		if (is_object($evprops))
			$evprops = array($evprops);
		$nevets = count($evprops);
		foreach ($evprops as $evprop) {
			try {
				$event = new Event($evprop);
			
				//updated event is updating it's reminder
				if ($evprop->has_reminder && $evprop->reminder_pref_id != null && !$evprop->using_calendar_reminder) {
					$evprop->user_id = Authorize:: sharedInstance()->userID();
					$evprop->version = $evprop->reminder_pref_version;
					$rpref = new ReminderPrefs($evprop);
					$rpref->update($pinsqli);
				}
				
				//updated event is adding a reminder
				if ($evprop->has_reminder && $evprop->reminder_pref_id == null && !$evprop->using_calendar_reminder) {
					$evprop->user_id = Authorize:: sharedInstance()->userID();
					ReminderPrefs:: create($evprop, $pinsqli);
				}
				
				//updated event is removing it's reminder
				if ($evprop->had_reminder) {
					$evprop->version = $evprop->reminder_pref_version;
					$rpref = new ReminderPrefs($evprop);
					$rpref->delete($pinsqli);
				}
				
				$event->update($pinsqli);
			
				echo json_encode(array($event->event_id => $event));
			} catch (EventDataConflictException $e) {
				echo $e->json_encode();
			} catch (EventDoesNotExist $e) {
				echo $e->json_encode();
			}
			if (--$nevets > 0) echo ',';
		}
		echo ']';
		User:: incrementVersion($authUserID);
	}
	function unsubscribe() {
		$subscription = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		//echo(json_encode(Calendar::unsubscribe($subscription, $authUserID)));
		User:: incrementVersion($authUserID);
	}

	function subscribe($body=NULL, $return=FALSE) {
		$subscription = ($body==NULL)? json_decode(Request:: body()):$body;
		$authUserID = Authorize:: sharedInstance()->userID();
		
		$event = array_shift(Event::load($subscription->event_id));
		$subscription->adhoc_events = true;
		$subscription->calendar_id = $event->calendar_id;
		error_log(print_r($subscription,true));
		$testSubscription = Sharing::isAdhocSubscription($body);
		if(!$testSubscription->exists){
			// add a new subscription of adhoc type
			$calendarSubscribe = Calendar::subscribe($subscription, $authUserID);
			$eventSubscribe = Event::subscribe($subscription, $authUserID);
		}
		elseif($testSubscription->exists && $testSubscription->adhoc){
			// add to existing adhoc subscription
			if(!Sharing::alreadySubscribedEvent($body)){
				$eventSubscribe = Event::subscribe($subscription, $authUserID);
			}else{
				//throw duplicate exception
			}
			
		}
		$subscription = json_encode(array('event'=> $event));
		if($return){
			User:: incrementVersion($authUserID);
			return json_decode($subscription);
		}else{
			echo $subscription;
		}
	}
	/**
	*	EventCtrl::delete provides an interface for deleting pre-existing events.
	*	The properties of the Event(s) to delete are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*	@return Returns '{}' or, in the case of delete conflicts(s), JSON Event
	*	Conflict(s) (see above).
	*
	*	Client sends full Event(s) to EventCtrl::delete interfce. If delete conflict(s) occur,
	*	EventCtrl::delete will return the Event(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless the local delte of the Same Event.
	*/
	function delete(){
		$evprops = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		echo '[';
		if (is_object($evprops))
			$evprops = array($evprops);
		$nevets = count($evprops);
		foreach ($evprops as $evprop) {
			try {
				$event = new Event($evprop);
				$event->delete();

				//if event has a reminder
				if ($evprop->reminder_pref_id != null && !$using_calendar_reminder) {
					$evprop->version = $evprop->reminder_pref_version;
					$reminder_pref = new ReminderPrefs($evprop);
					$reminder_pref->delete();
				}
				
				echo json_encode($event);
			} catch (EventDataConflictException $e) {
				echo $e->json_encode();
			} catch (EventDoesNotExist $e) {
				echo $e->json_encode();
			}
			if (--$nevets > 0) echo ',';
		}
		echo ']';
		User:: incrementVersion($authUserID);
	}
}
?>
