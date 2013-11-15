<?php
class Calendar extends PinwheelModelObject
{
	public $calendar_id;
	public $calendar_admin;
	public $calendar_name;
	public $create_time;
	public $creator_id;
	public $color;
	public $active;
	public $last_modified;
	public $version;
	public $has_reminder;
	public $mins_before;
	public $reminder_type;
	public $reminder_pref_id;
	public $reminder_pref_version;
	public $reminder_aggregate;
	public $public;
	public $viewing;


	/**
	*	The parent construct call static hook Calendar::defaults to assign
	*	initial values to Calendar's properties.
	*/
	static protected function defaults () {
		return array(
			'calendar_id' => NULL,
			'calendar_name' => '',
			'calendar_admin' => false,
			'color' => '',
			'create_time' => '',
			'creator_id' => '',
			'last_modified' => 0,
			'version' => 0,
			'subscribed' => false,
			'mins_before' => '',
			'has_reminder' => false,
			'reminder_type' => '',
			'reminder_pref_id' => '',
			'reminder_pref_version' => 0,
			'reminder_aggregate' => 1,
			'public' => false,
			'viewing' => false
		);
	}
	static public function unsubscribe($subscription, $userId){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"
			DELETE FROM
				calendar_subs
			WHERE
				user_id='$userId'
			AND
				calendar_id='{$subscription->calendar_id}'");

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);


		return $subscription;
	}


	static public function subscribe($subscription, $userId){
		//$subscription->adhoc_events = ($subscription->adhoc_events);
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"INSERT INTO calendar_subs (
					calendar_id,
					color,
					user_id,
					adhoc_events
				)
				Values (
					'{$subscription->calendar_id}',
					'{$subscription->color}',
					'$userId',
					'{$subscription->adhoc_events}'
				)");

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		return $subscription;
	}
	/**
	*	Calendar::factory builds Calendar(s) from passed properties. Consider, Calendar::factory
	*	does NOT consult the data-storage, see Calendar::reload for 'refreshing' a Calendar
	*	from datastore.
	*
	*	@param $p  Array of calendar-property-array/object(s) or a single
	*	calendar-property-array/object.
	*	@return Array of Calendar(s).
	*/
	static public function factory ($p) {
		// process argument
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p);
		// build calendars
		$calendars = array();
		foreach ($p as $tp)
			array_push($calendars, new Calendar($tp));
		return $calendars;
	}


	/**
	*	Calendar::load builds Calendar from datastore.
	*
	*	@param $id  Array of calendar-id-string(s) or a single calendar-id-string.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Array of Calendar(s).
	*/
	static public function load ($id, $pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT 
					calendars.calendar_id,
					public_calendars.org_id,
					if(count(public_calendars.calendar_id) = 1, true, false) AS public,
					UNIX_TIMESTAMP(create_time) as create_time,
					creator_id,
					if(calendar_subs.subscription_name IS NOT NULL, calendar_subs.subscription_name,calendar_name) AS calendar_name,
					calendar_subs.color,
					UNIX_TIMESTAMP(calendars.last_modified) as last_modified,
					calendars.active,
					calendars.version,
					reminder_prefs.mins_before,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id as reminder_pref_id,
					reminder_prefs.version as reminder_pref_version,
					reminder_prefs.reminder_pref_id as has_reminder,
					reminder_prefs.aggregate as reminder_aggregate,
					IF(calendar_admins.calendar_id is not null, TRUE, FALSE) as calendar_admin
				FROM calendars
				LEFT OUTER JOIN reminder_prefs
				ON calendars.calendar_id = reminder_prefs.calendar_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID' AND reminder_prefs.event_id = '' AND reminder_prefs.task_id = ''
				LEFT OUTER JOIN calendar_subs
				ON calendar_subs.calendar_id = calendars.calendar_id AND calendar_subs.user_id = '$authUserID'
				LEFT OUTER JOIN public_calendars
				ON public_calendars.calendar_id = calendars.calendar_id
				left outer join	calendar_admins
				ON 	calendars.calendar_id = calendar_admins.calendar_id AND calendar_admins.user_id = '$authUserID'
				WHERE calendars.calendar_id IN ($id)
			", $pinsqli);
			
			
			/*
			LEFT OUTER JOIN reminder_prefs
			ON events.id = reminder_prefs.id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
			*/
	}

	static public function loadUserCreatedCalendars($userId, $pinsqli=NULL){
		return(static:: genericQuery("SELECT 
					calendars.calendar_id,
					calendar_name,
					UNIX_TIMESTAMP(create_time) as create_time,
					creator_id,
					UNIX_TIMESTAMP(calendars.last_modified) as last_modified,
					calendars.active,
					calendars.version,
					reminder_prefs.mins_before,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id as reminder_pref_id,
					reminder_prefs.version as reminder_pref_version,
					reminder_prefs.reminder_pref_id as has_reminder,
					reminder_prefs.aggregate as reminder_aggregate
				from
					calendars
				left outer join
					reminder_prefs
				ON 
					calendars.calendar_id = reminder_prefs.calendar_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$userId' AND reminder_prefs.aggregate = TRUE
				left outer join
					public_calendars
				ON
					calendars.calendar_id = public_calendars.calendar_id
				where
					calendars.creator_id = '$userId'"));
	}

	static public function loadUserOrgCalendars($userId, $pinsqli=NULL){
		$authUserID = Authorize:: sharedInstance()->userID();
	
		return(static:: genericQuery(
			"SELECT 
					calendars.calendar_id,
					public_calendars.org_id,
					calendar_name,
					UNIX_TIMESTAMP(create_time) as create_time,
					creator_id,
					UNIX_TIMESTAMP(calendars.last_modified) as last_modified,
					calendars.active,
					calendars.version,
					reminder_prefs.mins_before,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id as reminder_pref_id,
					reminder_prefs.version as reminder_pref_version,
					reminder_prefs.reminder_pref_id as has_reminder,
					reminder_prefs.aggregate as reminder_aggregate,
					IF(calendar_admins.calendar_id is not null, TRUE, FALSE) as calendar_admin
				from calendars
				left outer join	reminder_prefs
				ON calendars.calendar_id = reminder_prefs.calendar_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$userId' AND reminder_prefs.aggregate = TRUE
				left outer join	public_calendars
				ON calendars.calendar_id = public_calendars.calendar_id
				left outer join	calendar_admins
				ON 	calendars.calendar_id = calendar_admins.calendar_id AND calendar_admins.user_id = '$authUserID'
				where
					calendars.creator_id = '$userId'
				OR
					public_calendars.org_id in (
						SELECT
							org_id
						FROM
							users_orgs
						WHERE
							user_id = '$userId')
				AND 
					calendars.calendar_id NOT IN (
						SELECT
							calendar_id
						FROM
							calendar_subs
						WHERE 
							user_id = '$userId')
				", $pinsqli));

	}
	static public function loadUserSubscriptions($userId, $pinsqli=NULL){	
		return(static:: genericQuery(
			"SELECT 
					calendars.calendar_id,
					UNIX_TIMESTAMP(create_time) as create_time,
					creator_id,
					UNIX_TIMESTAMP(calendars.last_modified) as last_modified,
					calendars.active,
					if(calendar_subs.subscription_name IS NOT NULL, calendar_subs.subscription_name,calendar_name) AS calendar_name,
					calendar_subs.color,
					calendars.version,
					calendar_subs.adhoc_events,
					reminder_prefs.mins_before,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id as reminder_pref_id,
					reminder_prefs.version as reminder_pref_version,
					reminder_prefs.reminder_pref_id as has_reminder,
					reminder_prefs.aggregate as reminder_aggregate,
					IF(calendar_admins.calendar_id is not null, TRUE, FALSE) as calendar_admin
				from
					calendars
				left outer join
					reminder_prefs
				ON 
					calendars.calendar_id = reminder_prefs.calendar_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$userId' AND reminder_prefs.aggregate = TRUE
				left outer join
					calendar_subs
				ON
					calendars.calendar_id = calendar_subs.calendar_id
				left outer join
					calendar_admins
				ON 
					calendars.calendar_id = calendar_admins.calendar_id AND calendar_admins.user_id = '$userId'
				where
					calendar_subs.user_id = '$userId'
				AND calendar_subs.calendar_id not in (
						SELECT
							calendar_id
						FROM
							calendars
						WHERE
							calendars.creator_id = '$userId')
", $pinsqli));
	}
	static public function getCalendarAdmins($id,$pinsqli=NULL) {
		return (static:: genericQuery(
			"SELECT calendar_admins.user_id,
				first_name,
				last_name,
				user_handle
				FROM calendar_admins	
				LEFT OUTER JOIN users
				ON users.user_id = calendar_admins.user_id
				WHERE calendar_admins.calendar_id = '{$id}'
				And users.active = true
	", $pinsqli));
	}
	static public function loadByQuery($query, $pinsqli=NULL){
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$calendars = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$calendars = new Calendar($object);
		} else
			throw new Exception($pinsqli->error, 1);

		return($calendars);
	}



	/**
	*	Calendar::create stores calendar(s) properties into datastore.
	*
	*	@param $p  Array of calendar-property-array/object(s) or a single
	*	calendar-property-array/object.
	*	@return Array of Calendar(s) built from datastore.
	*/
	static public function create ($id,$p) {
		//error_log($id);
		//error_log(print_r($p,true));
		// process argument
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative

		// build query
		$calendarIDs = array();
		$valueStrings = array();
		$defaults = static:: defaults();
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		foreach ($p as $tp) {
			if (is_object($tp))
				$tp = get_object_vars($tp);
			$tp = array_merge($defaults, $tp);
			$tp = array_map(array($pinsqli, 'real_escape_string'), $tp);
			$calendarID = MySQLConnection::generateUID('calendar');
			array_push($valueStrings,
				"(
					'$calendarID',
					'{$tp['calendar_name']}',
					'{$tp['color']}',
					NOW(),
					'{$id}'
				)"
			);
			array_push($calendarIDs, $calendarID);
		}
		$values = implode(',', $valueStrings);
		$resulti = $pinsqli->query(
			"INSERT INTO calendars (
					calendar_id,
					calendar_name,
					color,
					create_time,
					creator_id
				)
				Values $values
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		$subscription = (object) array('calendar_id' => $calendarIDs[0], 'color' => $p[0]['color']);

		static:: subscribe($subscription, $id);
		
		$calendars = static:: load($calendarIDs, $pinsqli);
		return $calendars;
	}
	
	public function update_reminders($pinsqli) {
		//error_log(print_r($this,true));
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		$properties = array_map(array($pinsqli, 'real_escape_string'), get_object_vars($this));
		
		//store calendar version
		$calendar_version = $this->version;
		
		//updated calendar is updating it's reminders
		if ($this->has_reminder && $this->reminder_pref_id != null) {
			$this->user_id = Authorize:: sharedInstance()->userID();
			$this->version = $this->reminder_pref_version;
			$this->aggregate = $this->reminder_aggregate;
			$rpref = new ReminderPrefs($this);
			$rpref->update($pinsqli);
		}
		//updated calendar is adding a reminder
		if ($this->has_reminder && $this->reminder_pref_id == null) {
			$this->user_id = Authorize:: sharedInstance()->userID();
			$this->aggregate = $this->reminder_aggregate;
			ReminderPrefs:: create($this, $pinsqli);
		}
		//updated calendar is removing it's reminder
		if (!$this->has_reminder && $this->reminder_pref_id != null) {
			$this->version = $this->reminder_pref_version;
			$this->has_reminder = null;
			$this->aggregate = $this->reminder_aggregate;
			$rpref = new ReminderPrefs($this);
			$rpref->delete($pinsqli);
		}
		
		$this->version = $calendar_version;	
	}
		
	/**
	*	Calendar::update_subscriptions will attempt to update properties stored in the calendar_subs
	*	corresponding to $this Calendar.
	*/
	
	public function update_subscriptions($pinsqli) {
		//connection and request properties
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		$properties = array_map(array($pinsqli, 'real_escape_string'), get_object_vars($this));

		//get user
		$authUserID = null;
		$authUserID = Authorize:: sharedInstance()->userID();
		
		$resulti = $pinsqli->query(
			"UPDATE calendar_subs
				SET
					color = '{$properties['color']}',
					subscription_name = '{$properties['calendar_name']}'
				WHERE calendar_id = '{$properties['calendar_id']}'
					AND user_id = '$authUserID'
			"
		);
		
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		$this->reload_subscriptions($pinsqli);
	}
	
	/**
	*	Calendar::reload_subscriptions will 'refresh' $this Calendar from calendar_subs
	*/
	
	public function reload_subscriptions($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		
		//get user
		$authUserID = null;
		$authUserID = Authorize:: sharedInstance()->userID();
	
		$resulti = $pinsqli->query(
			"SELECT color
				FROM calendar_subs
				WHERE calendar_id = '$this->calendar_id'
					AND user_id = '$authUserID'
			"
		);
		
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}

	/**
	*	Calendar::reload will 'refresh' $this Calendar from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT 
					calendars.calendar_id,
					public_calendars.org_id,
					UNIX_TIMESTAMP(create_time) as create_time,
					creator_id,
					UNIX_TIMESTAMP(calendars.last_modified) as last_modified,
					calendars.active,
					if(calendar_subs.subscription_name IS NOT NULL, calendar_subs.subscription_name,calendar_name) AS calendar_name,
					calendar_subs.color,
					calendars.version,
					calendar_subs.adhoc_events,
					reminder_prefs.mins_before,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id as reminder_pref_id,
					reminder_prefs.version as reminder_pref_version,
					reminder_prefs.reminder_pref_id as has_reminder,
					reminder_prefs.aggregate as reminder_aggregate,
					IF(calendar_admins.calendar_id is not null, TRUE, FALSE) as calendar_admin
				from calendars
				left outer join	public_calendars
				ON calendars.calendar_id = public_calendars.calendar_id
				left outer join	reminder_prefs
				ON calendars.calendar_id = reminder_prefs.calendar_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID' AND reminder_prefs.aggregate = TRUE
				left outer join	calendar_subs
				ON calendars.calendar_id = calendar_subs.calendar_id AND calendar_subs.user_id = '$authUserID'
				left outer join	calendar_admins
				ON 	calendars.calendar_id = calendar_admins.calendar_id AND calendar_admins.user_id = '$authUserID'
				WHERE calendars.calendar_id = '$this->calendar_id'
				AND calendars.version > $this->version				
			"
		);
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	Calendar::update will attempt to update properties stored in the datastore
	*	corresponding to $this Calendar.
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

		$resulti = $pinsqli->query(
			"UPDATE calendars
				SET
					calendar_name   = '{$properties['calendar_name']}',
					version         = version + 1
				WHERE calendar_id = '{$properties['calendar_id']}'
					AND version = $this->version
			"
		);

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->calendar_id, $pinsqli);
			//$resource = array_pop($resource);
			//error_log(print_r($resource,true));
			if (!$resource)
				throw new CalendarDoesNotExist($this);
			throw new CalendarDataConflictException($resource, array($this));
		}
		$this->reload($pinsqli);
		//error_log(print_r($this,true));
	}

	public function addCalendarAdmin($admin, $calendar_id){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"INSERT INTO calendar_admins(calendar_id,user_id)
				select '$calendar_id','{$admin->user_id}'	
				from dual		
				WHERE NOT EXISTS (
					SELECT * FROM calendar_admins WHERE calendar_id = '$calendar_id' AND user_id = '{$admin->user_id}'
				)"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
	}
	public function deleteCalendarAdmin($admin, $calendar_id){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"DELETE from calendar_admins
				WHERE user_id = '{$admin->user_id}'
				AND calendar_id = '$calendar_id'
				LIMIT 1"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
	}
	public function updateSubscription($calendar_id, $calendar_view, $user_id){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE calendar_subs
				SET
					view_setting = '$calendar_view'
				WHERE calendar_id = '$calendar_id'
					AND user_id = '$user_id'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
	}
	/**
	*	Calendar::delete will attempt to update properties stored in the datastore
	*	corresponding to $this Calendar.
	*
	*	Future Optimization:
	*	Above argument (update) does not apply. If a successful delete occurs between
	*	UPDATE and collision-sift query, then the collision is dropped for the resource
	*	is in desired state (f(a,t) = f(b,t) : a != b and f(...f(f(d,t))...) = f(d,t)
	*	where a,b,d are requests and t is calendar and f is delete-function). As a result,
	*	Calendar::delete could be batched.
	*/
	public function delete () {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE calendars
				SET
					active = FALSE,
					version = version + 1
				WHERE calendar_id = '$this->calendar_id'
					AND version = $this->version;
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->calendar_id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new CalendarDoesNotExist($this);
			throw new CalendarDataConflictException($resource, array($this));
		}
		$this->active = FALSE;
		$this->version += 1;
	}
	public function sendNewAdminMessage($recipients,$calendar_name) {
		$recipientString = implode(",", $recipients);

		$messageBody = Calendar::generateMessageBody($calendar_name);
		$share_id = MySQLConnection:: generateUID('new_admin');
		$postAuth = sha1($recipients[0]."rainbowkitties");

		$fields = array(
								'BatchId' => $share_id,
								'Destination' => $recipientString,
								'Subject' => 'Pinwheel Adminstrator Access Granted',
								'PlainBody' => $messageBody['plain'],
								'HtmlBody' => $messageBody['html'],
								'Auth' => $postAuth
						);

		$data = "";
		foreach( $fields as $key => $value ) $data .= "$key=" . urlencode( $value ) . "&";

		// run transaction
		$ch = curl_init("https://messenger-brc.sdicgdev.com"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, 1); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $data, "& " )); // use HTTP POST to send form data

		$result = curl_exec($ch);
	}
	public function generateMessageBody($calendar_name) {

		$authUserID = Authorize:: sharedInstance()->userID();
		$user = User:: load($authUserID);
		$user->first_name = ucfirst($user->first_name);
		$user->last_name = ucfirst($user->last_name);

		$messageBody = array();

		$messageBody['html'] = "<html>
									<body lang='en' style='background-color:#fff; color: #222'>
										<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
											<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
												<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
												$user->first_name $user->last_name has added you as an Administrator to <strong>$calendar_name</strong>.
											</h2>
											</div>
											<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
											<p>
												You have been given Administrator access to a Calendar that is owned or administered by $user->first_name $user->last_name.
											</p>
											<p>
												Any changes you make to this calendar will be visible to anyone else that subscribers to this calendar.
											</p>
											<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
												Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
											</p>
										</div>
									</div>
									</body>
								</html>";
		$messageBody['plain'] = $user->first_name." ".$user->last_name." has added you as an administrator to $calendar_name.
								You have been given access to a calendar that is owned or administered by $user->first_name $user->last_name.

								Any changes you make to this calendar will be visible to anyone else that subscribers to this calendar.

								Please do not reply to this message; it was sent from an unmonitored email address. This message is a service email related to your Pinwheel account.";
		return $messageBody;
	}
}