<?php
class Task extends PinwheelModelObject
{
	public $task_id;
	public $calendar_id;
	public $task_name;
	public $is_complete;
	public $create_time;
	public $due_time;
	public $creator_id;
	public $progress;
	public $active;
	public $last_modified;
	public $task_notes;
	public $version;
	public $mins_before;
	public $absolute_date;
	public $reminder_type;
	public $reminder_pref_id;
	public $reminder_pref_version;

	/**
	*	The parent construct call static hook Task::defaults to assign
	*	initial values to Task's properties.
	*/
	static protected function defaults () {
		return array(
			'task_id' => NULL,
			'calendar_id' => NULL,
			'task_name' => '',
			'is_complete' => 0,
			'create_time' => '',
			'due_time' => '',
			'creator_id' => '',
			'progress' => 0,
			'active' => 1,
			'last_modified' => 0,
			'task_notes' => NULL,
			'version' => 0,
			'mins_before' => '',
			'absolute_date' => '',
			'reminder_type' => '',
			'reminder_pref_id' => '',
			'reminder_pref_version' => 0
		);
	}


	public function init($properties){
	}
	/**
	*	Task::factory builds Task(s) from passed properties. Consider, Task::factory
	*	does NOT consult the data-storage, see Task::reload for 'refreshing' a Task
	*	from datastore.
	*
	*	@param $p  Array of task-property-array/object(s) or a single
	*	task-property-array/object.
	*	@return Array of Task(s).
	*/
	static public function factory ($p) {
		// process argument
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p);
		// build tasks
		$tasks = array();
		foreach ($p as $tp)
			array_push($tasks, new Task($tp));
		return $tasks;
	}


	/**
	*	Task::load builds Task from datastore.
	*
	*	@param $id  Array of task-id-string(s) or a single task-id-string.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Array of Task(s).
	*/
	static public function load ($id, $pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT 
					tasks.task_id,
					tasks.calendar_id,
					task_name,
					(select if(count(task_id) >= 1,1,0) from tasks_complete where tasks_complete.task_id = tasks.task_id AND tasks_complete.user_id = '$authUserID') as is_complete,
					UNIX_TIMESTAMP(create_time) as create_time,
					UNIX_TIMESTAMP(due_time) as due_time,
					creator_id,
					progress,
					UNIX_TIMESTAMP(tasks.last_modified) as last_modified,
					task_notes,
					tasks.active,
					tasks.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM tasks
				LEFT OUTER JOIN reminder_prefs
				ON tasks.task_id = reminder_prefs.task_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				WHERE tasks.task_id IN ($id)
			", $pinsqli);
	}

	static public function loadActive($id, $pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$id = is_array($id)? "'".implode("','", $id)."'": "'$id'";
		return static:: loadByQuery(
			"SELECT 
					tasks.task_id,
					tasks.calendar_id,
					task_name,
					(select if(count(task_id) >= 1,1,0) from tasks_complete where tasks_complete.task_id = tasks.task_id AND tasks_complete.user_id = '$authUserID') as is_complete,
					UNIX_TIMESTAMP(create_time) as create_time,
					UNIX_TIMESTAMP(due_time) as due_time,
					creator_id,
					progress,
					UNIX_TIMESTAMP(tasks.last_modified) as last_modified,
					task_notes,
					tasks.active,
					tasks.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM tasks
				LEFT OUTER JOIN reminder_prefs
				ON tasks.task_id = reminder_prefs.task_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				WHERE tasks.task_id IN ($id)
					AND tasks.active = TRUE
			", $pinsqli);
	}

	static public function loadByUser($userId, $pinsqli=NULL){
		return static:: loadByQuery(
			"SELECT
					tasks.task_id,
					tasks.calendar_id,
					task_name,
					(select if(count(task_id) >= 1,1,0) from tasks_complete where tasks_complete.task_id = tasks.task_id AND tasks_complete.user_id = '$authUserID') as is_complete,
					UNIX_TIMESTAMP(create_time) as create_time,
					UNIX_TIMESTAMP(due_time) as due_time,
					creator_id,
					progress,
					UNIX_TIMESTAMP(tasks.last_modified) as last_modified,
					task_notes,
					tasks.active,
					tasks.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM tasks
				LEFT OUTER JOIN reminder_prefs
				ON tasks.task_id = reminder_prefs.task_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$userId'
				WHERE tasks.creator_id = '$userId'
			", $pinsqli);
	}

	static public function getBatch($where_array=array(), $pinsqli=NULL){
		$authUserID = Authorize:: sharedInstance()->userID();
		$where_query = '';
		if($where_array && is_array($where_array) && sizeof($where_array) > 0){
			$where_query = " WHERE ".implode(' AND ', $where_array);
		}
		return static:: loadByQuery(
			"SELECT
					tasks.task_id,
					tasks.calendar_id,
					task_name,
					(select if(count(task_id) >= 1,1,0) from tasks_complete where tasks_complete.task_id = tasks.task_id AND tasks_complete.user_id = '$authUserID') as is_complete,
					UNIX_TIMESTAMP(create_time) as create_time,
					UNIX_TIMESTAMP(due_time) as due_time,
					creator_id,
					progress,
					UNIX_TIMESTAMP(tasks.last_modified) as last_modified,
					task_notes,
					tasks.active,
					tasks.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				FROM tasks
				LEFT OUTER JOIN reminder_prefs
				ON tasks.task_id = reminder_prefs.task_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				$where_query", $pinsqli);
	}

	static public function loadByQuery($query, $pinsqli=NULL){
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$tasks = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				$tasks[$object->task_id] = new Task($object);
		} else
			throw new Exception($pinsqli->error, 1);

		return($tasks);
	}



	/**
	*	Task::create stores task(s) properties into datastore.
	*
	*	@param $p  Array of task-property-array/object(s) or a single
	*	task-property-array/object.
	*	@return Array of Task(s) built from datastore.
	*/
	static public function create ($p, $pinsqli) {
		$authUserID = Authorize:: sharedInstance()->userID();
		// process argument
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative

		// build query
		$taskIDs = array();
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
			$taskID = MySQLConnection::generateUID('task');
			$tp['creator_id'] = $authUserID;
			array_push($valueStrings,
				"(
					'$taskID',
					'{$tp['calendar_id']}',
					'{$tp['task_name']}',
					'{$tp['is_complete']}',
					NOW(),
					FROM_UNIXTIME('{$tp['due_time']}'),
					'{$tp['creator_id']}',
					'{$tp['progress']}',
					'{$tp['active']}',
					'{$tp['task_notes']}'
				)"
			);
			if ($tp["has_reminder"] == true && !$tp['using_calendar_reminder']) {
				$tp["task_id"] = $taskID;
				$tp["user_id"] = $authUserID;

				if (isset($tp['event_id'])) {
					unset($tp['event_id']);
				}

				ReminderPrefs:: create($tp, $pinsqli);
			}
			array_push($taskIDs, $taskID);
		}
		$values = implode(',', $valueStrings);
		$resulti = $pinsqli->query(
			"INSERT INTO tasks (
					task_id,
					calendar_id,
					task_name,
					is_complete,
					create_time,
					due_time,
					creator_id,
					progress,
					active,
					task_notes
				)
				Values $values
			"
		);

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		$tasks = static:: load($taskIDs, $pinsqli);
		foreach ($tasks as $task)
			BRCDispatcher:: dispatchTaskModification(new BRCTaskModification($task, BRCTaskModification:: $Created));
		return $tasks;
	}

	
	/**
	*	Task::reload will 'refresh' $this Task from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$authUserID = Authorize:: sharedInstance()->userID();
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
					tasks.task_id,
					tasks.calendar_id,
					task_name,
					(select if(count(task_id) >= 1,1,0) from tasks_complete where tasks_complete.task_id = tasks.task_id AND tasks_complete.user_id = '$authUserID') as is_complete,
					UNIX_TIMESTAMP(create_time) as create_time,
					UNIX_TIMESTAMP(due_time) as due_time,
					creator_id,
					progress,
					UNIX_TIMESTAMP(tasks.last_modified) as last_modified,
					task_notes,
					tasks.active,
					tasks.version,
					reminder_prefs.mins_before,
					UNIX_TIMESTAMP(reminder_prefs.absolute_date) as absolute_date,
					reminder_prefs.reminder_type,
					reminder_prefs.reminder_pref_id,
					reminder_prefs.version as reminder_pref_version
				From tasks
				LEFT OUTER JOIN reminder_prefs
				ON tasks.task_id = reminder_prefs.task_id AND reminder_prefs.active = TRUE AND reminder_prefs.user_id = '$authUserID'
				WHERE tasks.task_id = '$this->task_id'
					AND tasks.version > $this->version
			"
		);
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				//error_log(print_r($object,true));
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	Task::update will attempt to update properties stored in the datastore
	*	corresponding to $this Task.
	*
	*	Non-optimization:
	*	Cannot batch because collision-sift query would not be atomic (scalar query result is
	*	atomic). Collision detection needs to be atomic; consider an update slipping in after
	*	UPDATE but before collision-sift query, the method would report a false positive
	*	collision.
	*/
	public function update () {
		$authUserID = Authorize:: sharedInstance()->userID();
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$properties = array_map(array($pinsqli, 'real_escape_string'), get_object_vars($this));
		if($properties['is_complete']){
			$resulti = $pinsqli->query(
				"INSERT INTO tasks_complete (
						task_id,
						user_id,
						last_modified
					)
					Values (
						'{$properties['task_id']}',
						'$authUserID',
						NOW()
						)
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		}else{
			$resulti = $pinsqli->query(
				"DELETE FROM tasks_complete
					WHERE user_id = '$authUserID'
					AND task_id = '{$properties['task_id']}'
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		}
		
		$resulti = $pinsqli->query(
			"UPDATE tasks
				SET
					task_name   = '{$properties['task_name']}',
					calendar_id = '{$properties['calendar_id']}',
					task_notes  = '{$properties['task_notes']}',
					due_time    = FROM_UNIXTIME('{$properties['due_time']}'),
					progress    = '{$properties['progress']}',
					version 	= version + 1
				WHERE task_id = '{$properties['task_id']}'
					AND version = $this->version
			"
		);

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->task_id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new TaskDoesNotExist($this);
			throw new TaskDataConflictException($resource, array($this));
		}
		$this->reload($pinsqli);
		BRCDispatcher:: dispatchTaskModification(new BRCTaskModification($this, BRCTaskModification:: $Updated));
	}


	/**
	*	Task::delete will attempt to update properties stored in the datastore
	*	corresponding to $this Task.
	*
	*	Future Optimization:
	*	Above argument (update) does not apply. If a successful delete occurs between
	*	UPDATE and collision-sift query, then the collision is dropped for the resource
	*	is in desired state (f(a,t) = f(b,t) : a != b and f(...f(f(d,t))...) = f(d,t)
	*	where a,b,d are requests and t is task and f is delete-function). As a result,
	*	Task::delete could be batched.
	*/
	public function delete () {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE tasks
				SET
					active = FALSE,
					version = version + 1
				WHERE task_id = '$this->task_id'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->task_id, $pinsqli);
			$resource = array_pop($resource);
			if (!$resource)
				throw new TaskDoesNotExist($this);
			throw new TaskDataConflictException($resource, array($this));
		}
		$this->active = FALSE;
		$this->version += 1;
		BRCDispatcher:: dispatchTaskModification(new BRCTaskModification($this, BRCTaskModification:: $Deleted));
	}
}
?>

