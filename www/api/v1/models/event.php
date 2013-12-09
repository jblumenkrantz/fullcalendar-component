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
	public $active;
	public $last_modified;
	public $version;
	public $allDay;
  public $stops_repeating;
  public $repeat_by_day;
  public $repeat_by_month;
  public $repeat_by_monthday;
  public $repeat_frequency;
  public $repeat_interval;
  public $repeat_addendum;
  public $repeat_blackout;
  public $repeat_position;
  public $repeat_id;
  public $repeat_stop;
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
			'repeat_by_day' => null,
			'repeat_by_month' => null,
			'repeat_by_monthday' => null,
			'repeat_frequency' => null,
			'repeat_interval' => null,
			'repeat_addendum' => null,
			'repeat_blackout' => null,
			'repeat_position' => null,
			'repeat_id' => null,
			'repeat_stop' => null,
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

	/** USED BY PINWHEEL **/
	static public function getUserEventsForCalendar($userId, $calendar, $start, $end, $pinsqli=NULL) {

		$dataRay = $eventArray = array();

		$events    = static::getBatch(array("events.active = true","events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"));
		
		$repeaters = static::getBatch(array("events.active = true","events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"), array("repeaters"=>true));

		$addendums = static::getBatch(array( "events.active = true","events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"), array("addendums"=>true));

		$addRay = array();
		// loop over addendums, assemble into arrays of repeaters
		foreach($addendums as $addendum){
			if(!IsSet($addRay[$addendum->repeat_id])){
				$addRay[$addendum->repeat_id] = array();
			}
			$addRay[$addendum->repeat_id][] = $addendum;
		}

		$onDate = $start;
		while($onDate <= $end){

			foreach($repeaters as $repeater){
				if($repeater->repeat_stop == null){
					$repeater->repeat_stop = INF;
				}
				$daysSince = static::daysSince($onDate, $repeater->start);
				$eventStart = strtotime("+$daysSince day", $repeater->start);
				if($onDate >= static::beginningOf($repeater->start) && $eventStart <= $repeater->repeat_stop){

					// find addendums for this repeaters
					// if one applies to this onDate, apply it and skip the switch
					if(IsSet($addRay[$repeater->id]) && IsSet($addRay[$repeater->id][0]) && static:: isOnDay($onDate, $addRay[$repeater->id][0]->start)){
						// knock the first event off the array
						$event = array_shift($addRay[$repeater->id]);
						if(!IsSet($event->repeat_blackout) && !$event->repeat_blackout){
							//apply the addendum if it isn't a blackout
							$events[] = $event;
						}
					}else{
						switch($repeater->repeat_frequency){
							case 'DAILY':
								if($daysSince%$repeater->repeat_interval == 0){
									$eventEnd = $eventStart+($repeater->end-$repeater->start);
									$events[] = Event::makeFrom($repeater, array('start'=>$eventStart, 'end'=>$eventEnd));
								}
								break;
							case 'WEEKLY':
								$by_day = explode(",", $repeater->repeat_by_day);
								if(
										(static::weeksSince($repeater->start, $onDate)%$repeater->repeat_interval == 0) &&
										(in_array(strtoupper(date('D', $onDate)), $by_day))
									){
										$eventStart = strtotime("+$daysSince day", $repeater->start);
										$eventEnd = $eventStart+($repeater->end-$repeater->start);
										$events[] = Event::makeFrom($repeater, array('start'=>$eventStart, 'end'=>$eventEnd));
									}
								break;
							case 'MONTHLY':
								$by_day = explode(",", $repeater->repeat_by_monthday);
								$stats = static::statsForDate($onDate);
								if(
										(isSet($repeater->repeat_position) &&
										$stats['dayName'] == $repeater->repeat_by_day &&
										$stats['currently'] == $repeater->repeat_position)
								 	||
										((static::monthsSince($repeater->start, $onDate)%$repeater->repeat_interval == 0) &&
										(in_array(strtoupper(date('d', $onDate)), $by_day)))
								){
										$eventStart = strtotime("+$daysSince day", $repeater->start);
										$eventEnd = $eventStart+($repeater->end-$repeater->start);
										$events[] = Event::makeFrom($repeater, array('start'=>$eventStart, 'end'=>$eventEnd));
								}

								break;
							case 'YEARLY':
								break;
						}
					}
				}
			}
			$onDate = strtotime("+1 day", $onDate);

		}


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

	static protected function statsForDate($date){
		$ray = array();
		$ray['dayName'] = strtoupper(date("D", $date));

		$startOfMonth = strtotime(date('Y-m-01', $date));

		$offsetFromFirst = strftime('%w', $date) - strftime('%w', $startOfMonth);
		if($offsetFromFirst < 0){
			$offsetFromFirst = 7 + $offsetFromFirst;
		}

		$ray['repeated']  = ceil((date('t', $date) - $offsetFromFirst)/7);
		$ray['currently'] = ceil((date('d', $date) - $offsetFromFirst)/7);
		return $ray;
	}

	static protected function beginningOf($date){
		return strtotime(strftime("%Y/%m/%d ", $date));
	}

	static protected function endOf($date){
		$date = static::beginningOf($date);
		$date = strtotime("+1 day", $date);
		return $date-1;
	}

	static public function makeFrom($event, $updateWith=array()){
		$event = clone $event;
		foreach($updateWith as $key => $value){
			$event->{$key} = $value;
		}
		unset($event->repeat_interval);
		unset($event->repeat_on);
		unset($event->repeat_by_day);
		unset($event->repeat_by_month);
		unset($event->repeat_by_monthday);
		unset($event->repeat_frequency);
		unset($event->repeat_stop);
		return $event;
	}

	static protected function isOnDay($start, $test){
		$end   = strtotime("+1 day", $start);
		return($test > $start && $test < $end);
	}

	static protected function monthsSince($start, $end){
		if($start > $end){
			$tmp = $end;
			$end = $start;
			$start = $tmp;
		}
		return date('m', $end)-date('m', $start);
	}

	static protected function weeksSince($start, $end){
		if($start > $end){
			$tmp = $end;
			$end = $start;
			$start = $tmp;
		}
		$startDaysSinceSunday = +(date('N', $start));
		if($startDaysSinceSunday!=7){
			$start = strtotime("-$startDaysSinceSunday days", $start);
		}
		return(floor(static::daysSince($start, $end)/7));
	}

	static protected function daysSince($onDate, $eventStart){
		$onDate = static::beginningOf($onDate);
		$eventStart = static::beginningOf($eventStart);
		$end = new DateTime();
		$end->setTimestamp($onDate);
		$start = new DateTime();
		$start->setTimestamp($eventStart);

		return $start->diff($end)->format("%a");
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
					FROM_UNIXTIME('{$ep['stops_repeating']}'),
					'{$ep['repeat_interval']}',
					'{$ep['repeat_frequency']}',
					'{$ep['repeat_by_day']}',
					'{$ep['repeat_by_month']}',
					'{$ep['repeat_by_monthday']}'
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
					stops_repeating,
					repeat_interval,
					repeat_frequency,
					repeat_by_day,
					repeat_by_month,
					repeat_by_monthday,
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
					UNIX_TIMESTAMP(stops_repeating) as stops_repeating,
					repeat_by_month,
					repeat_by_day,
					repeat_by_monthday,
					repeat_frequency,
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
	*	Event::getBatch will load Event derived from datastore conditioned
	*	on the passed clause.
	*
	*	@param $where_array  Array of conditions to impose on Event aquasition.
	*	@param $id_only  Flag indicating whether to return only id
	*	property from datastore.
	*	@return Array of Event(s).
	*/
	static public function getBatch($where_array=array(), $opts=array()){
		if(IsSet($opts['repeaters'])){
			$where_array[] = "events.repeat_addendum = '0'";
			$where_array[] = "events.repeat_interval > 0";
		}
		if(IsSet($opts['addendums'])){
			$where_array[] = "events.repeat_addendum = '1'";
			$opts['order'] = "events.start asc";
		}
		if(!IsSet($opts['addendums']) && !IsSet($opts['reminders'])){
			$where_array[] = "events.repeat_addendum = '0'";
			$where_array[] = "(events.repeat_interval IS NULL OR events.repeat_interval = '0')";
		}

		$authUserID = Authorize:: sharedInstance()->userID();
		$query = "SELECT ";

		if(!isSet($opts['id_only']) || $opts['id_only'] == false){
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
        repeat_interval,
        UNIX_TIMESTAMP(repeat_stop) as repeat_stop,
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

		if(IsSet($opts['order'])){
			$query .= "ORDER BY ".$opts['order'];
		}
		//error_log(print_r(preg_replace('~[\r\n\t]+~','',$query),true));
		// Query Datastore
		$pinsqli = DistributedMySQLConnection:: readInstance();
		//return $query;
		$resulti = $pinsqli->query($query);
		if ($pinsqli->errno){
			die($query);
			throw new Exception($pinsqli->error, 1);
		}

		// Unpack Result
		$events = array();
		while($event = $resulti->fetch_assoc()){
			if(!isSet($opts['id_only']) || $opts['id_only'] == false){
				$returnedEvent = new Event($event);
			}else{
				$returnedEvent = $event['id'];
			}
			array_push($events, $returnedEvent);
		}
		return($events);
	}
}

?>
