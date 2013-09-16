<?php
class TaskCtl
{


	/**
	*	TaskCtl::get provides an interface for aquiring tasks by means of
	*	the task-id(s) passed to the method.
	*
	*	@param $id  String of task-ids seperated by '/'
	*	@return JSON encode array of Task(s)
	*
	*	@todo HTTP CONDITIONAL GET
	*/
	function get($id) {
		$tasks = Task::loadActive(split('/', $id));
	 	echo json_encode($tasks);
	}

	/**
	* 	TaskCtl::getAll provides an interface for aquiring all tasks
	* 
	*	@return JSON encode array of Task(s)
	*
	* 	@todo get all tasks per signed in user
	*/
	function getAll(){
		$authUserID = Authorize:: sharedInstance()->userID();
		$tasks = Task::loadByUser($authUserID);
	 	echo json_encode($tasks);
	}


	/**
	*	TaskCtl::Create provides an interface for createing new tasks. The
	*	properties of the new Task(s) are passed into the session via the Request
	*	Body in the form of JSON.
	*
	*	@return Returns newly created JSON Task(s).
	*
	*	TaskCtl::create's query pattern is not atomic due to an INSERT followed
	*	by a SELECT without locking. There does exist a posibility,
	*	however unlikely, after the INSERT an update occurs before the following
	*	SELECT on the newly created Task(s), and if the task_id and
	*	last_modified where the only properties to be returned, the client's
	*	cached/local view of the Task(s) would be improperly bound to the wrong
	*	last_modified(s). As a result, the improperly bound last_modified(s) could
	*	lead to a false positives when performing future validations (CONDITIONAL GET),
	*	aka. lost of synchrony. To handle the lost of synchrony, TaskCtl::create
	*	returns full Task(s) to the client on return.
	*/
	function create() {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$authUserID = Authorize:: sharedInstance()->userID();
		$tasks = array_shift(Task:: create(json_decode(Request:: body()), $pinsqli));
		echo json_encode($tasks);
		User:: incrementVersion($authUserID);
	}

	/**
	*	TaskCtrl::update provides an interface for updating pre-existing tasks.
	*	The properties of the Task(s) to update are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*
	*	@return Returns updated JSON Task(s) or, in the case of update conflict(s),
	*	JSON Task Conflict(s) of the structure:
	*		{
	*			'errno'    :409,
	*			'message'  :...,
	*			'resource' :{<task>},
	*			'conflicts':[{<task1>}...{taskT}]}.
	*		}.
	*
	*
	*	Client sends full Task(s) to TaskCtrl::update interfce. If update conflict(s) occur,
	*	TaskCtrl::update will return the Task(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless of local client changes made to
	*	the same Task in the interim of request.
	*/
	function update()  {
		$tsprops = json_decode(Request:: body());
		$authUserID = Authorize:: sharedInstance()->userID();
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		if (is_object($tsprops))
			$tsprops = array($tsprops);
		$ntasks = count($tsprops);
		foreach ($tsprops as $tsprop) {
			try {
				$task = new Task($tsprop);

				//strip off event_id for reminder prefs
				if (isset($tsprop->event_id)) {
					unset($tsprop->event_id);
				}
				
				//updated task is updating it's reminder
				if (@$tsprop->has_reminder && $tsprop->reminder_pref_id != null && !$tsprop->using_calendar_reminder) {
					$tsprop->user_id = Authorize:: sharedInstance()->userID();
					$tsprop->version = $tsprop->reminder_pref_version;
					$rpref = new ReminderPrefs($tsprop);
					$rpref->update($pinsqli);
				}
				
				//updated task is adding a reminder
				if (@$tsprop->has_reminder && $tsprop->reminder_pref_id == null && !$tsprop->using_calendar_reminder) {
					$tsprop->user_id = Authorize:: sharedInstance()->userID();
					ReminderPrefs:: create($tsprop, $pinsqli);
				}
				
				//updated task is removing it's reminder
				if (@$tsprop->had_reminder) {
					$tsprop->version = $tsprop->reminder_pref_version;
					$rpref = new ReminderPrefs($tsprop);
					$rpref->delete($pinsqli);
				}
				
				$task->update($pinsqli);
				
				echo json_encode($task);
			} catch (TaskDataConflictException $e) {
				echo $e->json_encode();
			} catch (TaskDoesNotExist $e) {
				echo $e->json_encode();
			}
			if (--$ntasks > 0) echo ',';
		}
		User:: incrementVersion($authUserID);
	}

	/**
	*	TaskCtrl::delete provides an interface for deleting pre-existing tasks.
	*	The properties of the Task(s) to delete are passed into the session via
	*	the Request Body in the form of JSON.
	*
	*	@return Returns '{}' or, in the case of delete conflicts(s), JSON Task
	*	Conflict(s) (see above).
	*
	*	Client sends full Task(s) to TaskCtrl::delete interfce. If delete conflict(s) occur,
	*	TaskCtrl::delete will return the Task(s) in the 'conflicts' structure (above) to allow
	*	the client to settle the conflict, regardless the local delte of the Same Task.	
	*/
	function delete(){
		$task = Request:: parsePath();
		$authUserID = Authorize:: sharedInstance()->userID();
		$task['task_id'] = $task[2];
		$task['version'] = $task[3];
		unset($task[0], $task[1], $task[2], $task[3]);
		$task = new Task($task);
		$task->delete();

		//if event has a reminder and
		if ($task->reminder_pref_id != null && !$using_calendar_reminder) {
			$task->version = $task->reminder_pref_version;
			$reminder_pref = new ReminderPrefs($tsprop);
			$reminder_pref->delete();
		}
		echo json_encode($task);
		User:: incrementVersion($authUserID);
	}
}
?>
