<?php
class Event extends PinwheelModelObject
{
	public $id;
	public $title;
	public $event_description;
	public $calendar_id;
	public $start;
	public $end;
	public $opponent_id;
	public $location_id;
	public $creator_id;
	public $repeat_id;
	public $active;
	public $last_modified;
	public $version;
	public $allDay;
  public $stops_repeating;
  public $repeat_by;
  public $repeat_on;
  public $repeat_interval;
	public $mins_before;
	public $absolute_date;
	public $reminder_type;
	public $reminder_pref_id;
	public $reminder_pref_version;


	/**
	*	The parent construct call static hook Event::defaults to assign
	*	initial values to Event's properties.
	*/
	static protected function defaults () {
		return array(
			'id' => NULL,
			'title' => '',
			'event_description' => '',
			'calendar_id' => '',
			'start' => '',
			'end' => '',
			'opponent_id' => '',
			'location_id' => '',
			'creator_id' => '',
			'repeat_id' => null,
			'repeat_by' => null,
			'repeat_on' => null,
			'repeat_interval' => null,
			'allDay' => 0,
			'last_modified' => NULL,
			'version' => 0,
			'mins_before' => '',
			'absolute_date' => '',
			'reminder_type' => '',
			'reminder_pref_id' => '',
			'reminder_pref_version' => 0
		);
	}


	/**
	*	Event::factory builds Event(s) from passed properties. Consider, Event::factory
	*	does NOT consult the data-storage, see Event::reload for 'refreshing' an Event
	*	from datastore.
	*
	*	@param $p  Array of event-property-array/object(s) or a single
	*	event-property-array/object.
	*	@return Array of Event(s).
	*/
	static public function factory ($p) {
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative
		$events = array();
		foreach ($p as $ep)
			array_push($events, new Event($ep));
		return $events;
	}


	/**
	*	Event::load builds Event from datastore.
	*
	*	@param $id  Array of event-id-string(s) or a single event-id-string.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Array of Event(s).
	*/
	static public function load ($id, $pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT 
					events.id,
					title,
					event_description,
					events.calendar_id,
					UNIX_TIMESTAMP(start) as start,
					UNIX_TIMESTAMP(end) as end,
					opponent_id,
					location_id,
					creator_id,
					repeat_id,
					UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
					repeat_by,
					repeat_on,
					repeat_interval,
					events.active,
					allDay,
					UNIX_TIMESTAMP(events.last_modified) as last_modified,
					events.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM events
				LEFT OUTER JOIN reminder_prefs
				ON events.id = reminder_prefs.event_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				WHERE events.id IN ($id)
			"
		, $pinsqli);
	}
	
	static public function loadByUser($userId, $pinsqli=NULL){
		return static:: loadByQuery(
			"SELECT
					events.id,
					title,
					event_description,
					events.calendar_id,
					UNIX_TIMESTAMP(start) as start,
					UNIX_TIMESTAMP(end) as end,
					opponent_id,
					location_id,
					creator_id,
					repeat_id,
					UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
					repeat_by,
					repeat_on,
					repeat_interval,
					events.active,
					allDay,
					UNIX_TIMESTAMP(events.last_modified) as last_modified,
					events.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM events
				LEFT OUTER JOIN reminder_prefs
				ON events.id = reminder_prefs.event_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$userId' 
				WHERE events.creator_id = '$userId'
			", $pinsqli);
	}

	static public function loadActive ($id, $pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT
					events.id,
					title,
					event_description,
					events.calendar_id,
					UNIX_TIMESTAMP(start) as start,
					UNIX_TIMESTAMP(end) as end,
					opponent_id,
					location_id,
					creator_id,
					repeat_id,
					UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
					repeat_by,
					repeat_on,
					repeat_interval,
					events.active,
					allDay,
					UNIX_TIMESTAMP(events.last_modified) as last_modified,
					events.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM events
				LEFT OUTER JOIN reminder_prefs
				ON events.id = reminder_prefs.event_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID' 
				WHERE events.id IN ($id)
					AND events.active = TRUE
			"
		, $pinsqli);
	}

	/** USED BY PINWHEEL **/
	static public function getUserEventsForCalendar($userId, $calendar, $pinsqli=NULL) {
		$dataRay = $eventArray = array();

		//error_log(print_r($calendar,true));

		$events = Event::getBatch(array("events.active = true","events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"));


		/*if(isSet($calendar) && property_exists($calendar, 'adhoc_events') && !$calendar->adhoc_events){
			unset($calendar->adhoc_events);
		}*/
		foreach($events as $event){
			$event->active = (bool)($event->active);
			$event->allDay = (bool)($event->allDay);
			$event->editable = (bool)(($userId == $calendar->creator_id) || $calendar->calendar_admin);
			array_push($dataRay, $event);
		}
		return($dataRay);
	}
	

	static public function getEventsBetween($userId, $start, $end, $pinsqli=NULL) {
		$dataRay = $eventArray = array();

		$cals = (object) array_merge((array) Calendar::loadUserCreatedCalendars($userId), (array) Calendar::loadUserSubscriptions($userId));

		foreach($cals as $calendar){

			$events = Event::getBatch(array("events.active = true","start > FROM_UNIXTIME('$start')","start < FROM_UNIXTIME('$end')","events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"));

			if(property_exists($calendar, 'adhoc_events') && !$calendar->adhoc_events){
				unset($calendar->adhoc_events);
			}
			foreach($events as $event){
				array_push($dataRay, $event);
			}
		}
		return($dataRay);
	}
	static public function loadByQuery ($query, $pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$events = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
			array_push($events, new Event($object));
				//array_push($events, new Event($object));  // why were we doing it this way?
		} else
			throw new Exception($pinsqli->error, 1);
		return $events;
	}

	/**
	*	Event::create stores event(s) properties into datastore.
	*
	*	@param $p  Array of event-property-array/object(s) or a single
	*	event-property-array/object.
	*	@return Array of Event(s) built from datastore.
	*/
	static public function subscribe($subscription, $userId){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"INSERT INTO event_subs (
					id,
					calendar_id,
					user_id
				)
				Values (
					'{$subscription->id}',
					'{$subscription->calendar_id}',
					'$userId'
				)");

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		return $subscription;
	}
	static public function removeAdhocEvents($subscription, $userId){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"
			DELETE FROM
				event_subs
			WHERE
				user_id='$userId'
			AND
				calendar_id='{$subscription->calendar_id}'");
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

	static public function create ($p, $pinsqli) {
		$authUserID = Authorize:: sharedInstance()->userID();
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative

		$eventIDs = array();
		$valueStrings = array();
		$defaults = static:: defaults();
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		foreach ($p as $ep) {
			if (is_object($ep))
				$ep = get_object_vars($ep);
			$ep = array_merge($defaults, $ep);
			$ep = array_map(array($pinsqli, 'real_escape_string'), $ep);
			$eventID = MySQLConnection::generateUID('event');
			$ep['creator_id'] = $authUserID;
			if($ep['repeat_id'] == $ep['id']){
				$ep['repeat_id'] = $eventID;
			}
			array_push($valueStrings,
				"(
					'$eventID',
					'{$ep['title']}',
					'{$ep['event_description']}',
					'{$ep['calendar_id']}',
					FROM_UNIXTIME('{$ep['start']}'),
					FROM_UNIXTIME('{$ep['end']}'),
					'{$ep['opponent_id']}',
					'{$ep['location_id']}',
					'{$ep['creator_id']}',
					'{$ep['allDay']}',
					'{$ep['repeat_id']}',
					FROM_UNIXTIME('{$ep['stops_repeating']}'),
					'{$ep['repeat_interval']}',
					'{$ep['repeat_on']}',
					'{$ep['repeat_by']}'
				)"
			);
			if ($ep["has_reminder"] == true && !$ep['using_calendar_reminder']) {
				$ep["id"] = $eventID;
				$ep["user_id"] = $authUserID;
				ReminderPrefs:: create($ep, $pinsqli);
			}
			array_push($eventIDs, $eventID);
		}
		$values = implode(',', $valueStrings);

		$resulti = $pinsqli->query(
			"INSERT INTO events (
					id,
					title,
					event_description,
					calendar_id,
					start,
					end,
					opponent_id,
					location_id,
					creator_id,
					allDay,
					repeat_id,
					stops_repeating,
					repeat_interval,
					repeat_on,
					repeat_by
				)
				Values $values
			"
		);
		
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		$events = static:: load($eventIDs, $pinsqli);
		foreach ($events as $event)
			$event->active = (bool)($event->active);
			$event->allDay = (bool)($event->allDay);
			BRCDispatcher:: dispatchEventModification(new BRCEventModification($event, BRCEventModification:: $Created));
		return $events;
	}


	/**
	*	Event::reload will 'refresh' $this Event from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
					events.id,
					title,
					event_description,
					events.calendar_id,
					UNIX_TIMESTAMP(start) as start,
					UNIX_TIMESTAMP(end) as end,
					opponent_id,
					location_id,
					creator_id,
					repeat_id,
					UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
					repeat_by,
					repeat_on,
					repeat_interval,
					events.active,
					allDay,
					UNIX_TIMESTAMP(events.last_modified) as last_modified,
					events.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				From events
				LEFT OUTER JOIN reminder_prefs
				ON events.id = reminder_prefs.event_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				WHERE events.id = '$this->id'
					AND events.version > $this->version
			"
		);
		
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	Event::update will attempt to update properties stored in the datastore
	*	corresponding to $this Event.
	*
	*	Non-optimization:
	*	Cannot batch because collision-sift query would not be atomic (scalar query result is
	*	atomic). Collision detection needs to be atomic; consider an update slipping in after
	*	UPDATE but before collision-sift query, the method would report a false positive
	*	collision.
	*/
	public function update ($pinsqli) {
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		$properties = array_map(array($pinsqli, 'real_escape_string'), get_object_vars($this));
		if(!IsSet($properties['allDay'])){
			$properties['allDay'] = 0;
		}
		
		$resulti = $pinsqli->query(
			"UPDATE events
				SET
					title = '{$properties['title']}',
					event_description = '{$properties['event_description']}',
					calendar_id = '{$properties['calendar_id']}',
					start = FROM_UNIXTIME('{$properties['start']}'),
					end   = FROM_UNIXTIME('{$properties['end']}'),
					opponent_id = '{$properties['opponent_id']}',
					location_id = '{$properties['location_id']}',
					creator_id  = '{$properties['creator_id']}',
					allDay     = '{$properties['allDay']}',
					repeat_id   = '{$properties['repeat_id']}',
					stops_repeating = FROM_UNIXTIME('{$properties['stops_repeating']}'),
					repeat_interval = '{$properties['repeat_interval']}',
					repeat_on   = '{$properties['repeat_on']}',
					repeat_by   = '{$properties['repeat_by']}',
					version 	  = version + 1
				WHERE id = '{$properties['id']}'
					AND version = $this->version
			"
		);
		
		if ($pinsqli->errno) {
			var_dump("UPDATE events
				SET
					title = '{$properties['title']}',
					event_description = '{$properties['event_description']}',
					calendar_id = '{$properties['calendar_id']}',
					start = FROM_UNIXTIME('{$properties['start']}'),
					end   = FROM_UNIXTIME('{$properties['end']}'),
					opponent_id = '{$properties['opponent_id']}',
					location_id = '{$properties['location_id']}',
					creator_id  = '{$properties['creator_id']}',
					allDay  = {$properties['allDay']},
					repeat_id   = '{$properties['repeat_id']}',
					stops_repeating = FROM_UNIXTIME('{$properties['stops_repeating']}'),
					repeat_interval = '{$properties['repeat_interval']}',
					repeat_on   = '{$properties['repeat_on']}',
					repeat_by   = '{$properties['repeat_by']}',
					version 	= version + 1
				WHERE id = '{$properties['id']}'
					AND version = $this->version
			");
			throw new Exception($pinsqli->error, 1);
		}
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new EventDoesNotExist($this);
			throw new EventDataConflictException($resource, array($this));
		}
		$this->reload($pinsqli);
		$this->active = (bool)($this->active);
		$this->allDay = (bool)($this->allDay);
		BRCDispatcher:: dispatchEventModification(new BRCEventModification($this, BRCEventModification:: $Updated));
	}


	/**
	*	Event::delete will attempt to update properties stored in the datastore
	*	corresponding to $this Event.
	*
	*	Future Optimization:
	*	Above argument (update) does not apply. If a successful delete occurs between
	*	UPDATE and collision-sift query, then the collision is dropped for the resource
	*	is in desired state (f(a,e) = f(b,e) : a != b and f(...f(f(d,e))...) = f(d,e)
	*	where a,b,d are requests and e is event and f is delete-function). As a result,
	*	Event::delete could be batched.
	*/
	public function delete ($pinsqli=NULL) {
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		$resulti = $pinsqli->query(
			"UPDATE events
				SET
					active = FALSE,
					version = version + 1
				WHERE id = '$this->id'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new EventDoesNotExist($this);
			throw new EventDataConflictException($resource, array($this));
		}
		$this->version += 1;
		$this->active = FALSE;
		BRCDispatcher:: dispatchEventModification(new BRCEventModification($this, BRCEventModification:: $Deleted));
	}


	/**
	*	Event::allBetween wraps Event::getBatch with convenience for temporal 
	*	window condition.
	*
	*	@param $start  Start time (string) of window.
	*	@param $end  End time (string) of window.
	*	@return Array of Event(s).
	*/
	static public function allBetween($start, $end, $calendar_id=NULL){
		$where_array = array();
		$where_array[] = "start > FROM_UNIXTIME('$start')";
		$where_array[] = "start < FROM_UNIXTIME('$end')";
		if($calendar_id){
			$where_array[] = "events.calendar_id = '$calendar_id'";
		}
		return(Event::getBatch($where_array, array('id_only'=>false)));
	}


	/**
	*	Event::getBatch will load Event derived from datastore conditioned
	*	on the passed clause.
	*
	*	@param $where_array  Array of conditions to impose on Event aquasition.
	*	@param $id_only  Flag indicating whether to return only id
	*	property from datastore.
	*	@return Array of Event(s).
	*/
	static public function getBatch($where_array=array(), $opts=array()){
		$authUserID = Authorize:: sharedInstance()->userID();
		$query = "SELECT ";

		if(!isSet($opts['id_only']) || $opts['id_only'] == false){
			$opts = array_keys(get_class_vars('Event'));
			$query .= "
				events.id,
				title,
				event_description,
				events.calendar_id,
				UNIX_TIMESTAMP(start) as start,
				UNIX_TIMESTAMP(end) as end,
				opponent_id,
				location_id,
				creator_id,
        repeat_id,
        repeat_interval,
        repeat_on,
        repeat_by,
				UNIX_TIMESTAMP(events.last_modified) as last_modified,
				UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
				events.active,
				allDay,
				events.version,
				reminder_prefs.mins_before,
				UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
				reminder_prefs.reminder_type,
				reminder_prefs.reminder_pref_id,
				reminder_prefs.version as reminder_pref_version
			";
		}else{
			$query .= 'events.id';
		}
		$query .= " FROM events LEFT OUTER JOIN reminder_prefs ON events.id = reminder_prefs.event_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'";
		
		$where_query = '';
		if($where_array && is_array($where_array) && sizeof($where_array) > 0){
			$where_query = " WHERE ".implode(' AND ', $where_array);
		}

		$query .= $where_query;
		//error_log(print_r(preg_replace('~[\r\n\t]+~','',$query),true));
		// Query Datastore
		$pinsqli = DistributedMySQLConnection:: readInstance();
		//return $query;
		$resulti = $pinsqli->query($query);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		// Unpack Result
		$events = array();
		while($event = $resulti->fetch_assoc()){
			if(!isSet($opts['id_only']) || $opts['id_only'] == false){
				$returnedEvent = new Event($event);
			}else{
				$returnedEvent = $event['id'];
			}
			array_push($events, $returnedEvent);
			//$events[$event['id']] = $returnedEvent;
		}
		return($events);
	}
}

?>
