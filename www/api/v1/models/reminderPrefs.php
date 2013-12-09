<?php

class ReminderPrefs extends PinwheelModelObject
{
	public $reminder_pref_id;
	public $calendar_id;
	public $event_id;
	public $task_id;
	public $event_reminder_pref;
	public $user_id;
	public $mins_before;
	public $absolute_date;
	public $relative;
	public $aggregate;
	public $active;
	public $last_modified;
	public $version;
	public $reminder_type;


	/**
	*	The parent construct call static hook ReminderPrefs::defaults to assign
	*	initial values to ReminderPrefs's properties.
	*/
	static protected function defaults () {
		return array(
			'reminder_pref_id' => NULL,
			'calendar_id' => NULL,
			'event_id' => NULL,
			'task_id' => NULL,
			'event_reminder_pref' => 1,
			'user_id' => NULL,
			'mins_before' => 0,
			'absolute_date' => 0,
			'relative' => 1,
			'aggregate' => 0,
			'active' => 1,
			'last_modified' => 0,
			'version' => 0,
			'reminder_type' => NULL
		);
	}


	/**
	*	ReminderPrefs::factory builds ReminderPrefs(s) from passed properties. Consider,
	*	ReminderPrefs::factory does NOT consult the data-storage, see ReminderPrefs::reload
	*	for 'refreshing' a ReminderPref from datastore.
	*
	*	@param $p  Array of rpref-property-array/object(s) or a single
	*	rpref-property-array/object.
	*	@return Array of ReminderPrefs(s).
	*/
	static public function factory ($p) {
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p);
		// build rprefs
		$rprefs = array();
		foreach ($p as $tp)
			array_push($rprefs, new ReminderPrefs($tp));
		return $rprefs;
	}

	static public function loadCalendarReminders($userId, $pinsqli = NULL) {
		return static:: loadByQuery("SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE user_id = '$userId'
					AND active = TRUE
				AND event_id = ''
				AND task_id = ''", $pinsqli);
	}

	static public function loadEventReminders($userId, $pinsqli = NULL) {
		return static:: loadByQuery("SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE user_id = '$userId'
					AND active = TRUE
				AND event_id != ''
				AND task_id = ''", $pinsqli);
	}

	static public function loadTaskReminders($userId, $pinsqli = NULL) {
		return static:: loadByQuery("SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE user_id = '$userId'
					AND active = TRUE
					AND event_id = ''
					AND task_id != ''", $pinsqli);
	}
	/**
	*	ReminderPrefs::load builds ReminderPref from datastore.
	*
	*	@param $id  Array of rpref-id-string(s) or a single rpref-id-string.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Array of ReminderPrefs(s).
	*/
	static public function load ($id, $pinsqli = NULL) {
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE reminder_pref_id IN ($id)
			", $pinsqli);
	}

	static public function loadActive ($id, $pinsqli = NULL) {
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE reminder_pref_id IN ($id)
					AND active = TRUE
			", $pinsqli);
	}

	static public function loadActiveByUser ($id, $userID, $pinsqli = NULL) {
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE reminder_pref_id IN ($id)
					AND active = TRUE
					AND user_id = '$userID'
			", $pinsqli);
	}

	static public function loadByUser ($userId, $pinsqli = NULL) {
		return static:: loadByQuery(
			"SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				FROM reminder_prefs
				WHERE user_id = '$userId'
					AND active = TRUE
			", $pinsqli);
	}

	static public function loadByQuery ($query, $pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$rprefs = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$rprefs[] = new ReminderPrefs($object);
		} else
			throw new Exception($pinsqli->error, 1);

		return($rprefs);
	}


	/**
	*	ReminderPrefs::create stores reminderPrefs(s) properties into datastore.
	*
	*	@param $p  Array of rpref-property-array/object(s) or a single
	*	rpref-property-array/object.
	*	@return Array of ReminderPrefs(s) built from datastore.
	*/
	static public function create ($p, $pinsqli) {
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative

		// build query
		$rprefIDs = array();
		$valueStrings = array();
		$defaults = static:: defaults();
		
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		
		foreach ($p as $tp) {
			if (is_object($tp))
				$tp = get_object_vars($tp);
			$tp = array_merge($defaults, $tp);
			$tp = array_map(array($pinsqli, 'real_escape_string'), $tp);
			$rprefID = MySQLConnection::generateUID('reminder_pref');

			$eventID = (strpos($tp['id'], 'event') !== false) ? $tp['id'] : "";
			$taskID = (strpos($tp['id'], 'task') !== false) ? $tp['id'] : "";
			
			array_push($valueStrings,
				"(
					'$rprefID',
					'{$tp['calendar_id']}',
					'{$eventID}',
					'{$taskID}',
					'{$tp['event_reminder_pref']}',
					'{$tp['user_id']}',
					'{$tp['mins_before']}',
					FROM_UNIXTIME('{$tp['absolute_date']}'),
					'{$tp['relative']}',
					'{$tp['aggregate']}',
					'{$tp['reminder_type']}'
				)"
			);
			array_push($rprefIDs, $rprefID);
		}
		$values = implode(',', $valueStrings);
		$pinsqli->query(
			"INSERT INTO reminder_prefs (
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					absolute_date,
					relative,
					aggregate,
					reminder_type
				)
				Values $values
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		$rprefs = static:: load($rprefIDs, $pinsqli);
		foreach ($rprefs as $rpref)
			BRCDispatcher:: dispatchReminderPrefModification(
				new BRCReminderPrefModification($rpref, BRCReminderPrefModification:: $Created)
			);
		return $rprefs;
	}


	/**
	*	ReminderPrefs::reload will 'refresh' $this ReminderPrefs from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
					reminder_pref_id,
					calendar_id,
					event_id,
					task_id,
					event_reminder_pref,
					user_id,
					mins_before,
					UNIX_TIMESTAMP(absolute_date) as absolute_date,
					relative,
					aggregate,
					active,
					UNIX_TIMESTAMP(last_modified) as last_modified,
					version,
					reminder_type
				From reminder_prefs
				WHERE reminder_pref_id = '$this->reminder_pref_id'
					AND version > $this->version
			"
		);
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	ReminderPrefs::update will attempt to update properties stored in the datastore
	*	corresponding to $this ReminderPrefs.
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

		/** Do not update 'active'! We want to maintain active->dead but not dead->active **/
		$resulti = $pinsqli->query(
			"UPDATE reminder_prefs
				SET
					calendar_id   = '{$properties['calendar_id']}',
					event_id      = '{$properties['event_id']}',
					task_id       = '{$properties['task_id']}',
					event_reminder_pref       = '{$properties['event_reminder_pref']}',
					user_id       = '{$properties['user_id']}',
					mins_before   = '{$properties['mins_before']}',
					absolute_date = FROM_UNIXTIME('{$properties['absolute_date']}'),
					relative      = '{$properties['relative']}',
					aggregate      = '{$properties['aggregate']}',
					version 	= version + 1,
					reminder_type = '{$properties['reminder_type']}'
				WHERE reminder_pref_id = '{$properties['reminder_pref_id']}'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->reminder_pref_id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new ReminderPrefDoesNotExist($this);
			throw new ReminderPrefDataConflictException($resource, array($this));
		}
		$this->reload($pinsqli);
		BRCDispatcher:: dispatchReminderPrefModification(
			new BRCReminderPrefModification($this, BRCReminderPrefModification:: $Updated)
		);
	}


	/**
	*	ReminderPrefs::delete will attempt to update properties stored in the datastore
	*	corresponding to $this ReminderPrefs.
	*
	*	Future Optimization:
	*	Above argument (update) does not apply. If a successful delete occurs between
	*	UPDATE and collision-sift query, then the collision is dropped for the resource
	*	is in desired state (f(a,r) = f(b,r) : a != b and f(...f(f(d,r))...) = f(d,r)
	*	where a,b,d are requests and r is remindPref and f is delete-function). As a result,
	*	ReminderPrefs::delete could be batched.
	*/
	public function delete ($pinsqli) {
		if (!isset($pinsqli)) {
			$pinsqli = DistributedMySQLConnection:: writeInstance();
		}
		$resulti = $pinsqli->query(
			"UPDATE reminder_prefs
				SET
					active = FALSE,
					version = version + 1
				WHERE reminder_pref_id = '$this->reminder_pref_id'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->reminder_pref_id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new ReminderPrefDoesNotExist($this);
			throw new ReminderPrefDataConflictException($resource, array($this));
		}
		$this->version += 1;
		$this->active = FALSE;
		BRCDispatcher:: dispatchReminderPrefModification(
			new BRCReminderPrefModification($this, BRCReminderPrefModification:: $Deleted)
		);
	}
}
