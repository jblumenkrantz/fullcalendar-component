<?php

class BRCTaskModification extends Symfony\Component\EventDispatcher\Event
{
	static public $Topic = 'com.schooldatebooks.brc.BRCTaskModification';
	static public $Created = 'com.schooldatebooks.brc.BRCTaskModification::Created';
	static public $Updated = 'com.schooldatebooks.brc.BRCTaskModification::Updated';
	static public $Deleted = 'com.schooldatebooks.brc.BRCTaskModification::Deleted';
	protected $task;
	protected $type;

	public function __construct (Task $task, $type) {
		$this->task = $task;
		$this->type = $type;
	}

	public function task () {
		return $this->task;
	}

	public function type () {
		return $this->type;
	}

	public function __get ($property) {
		if (property_exists($this, $property) && method_exists($this, $property))
			return call_user_func(array($this, $property));
		else
			throw new Exception(sprintf(
				"missing %s::$%s getter.\n", get_class($this), $property), 1
			);
	}

	public function json_encode() {
		$ts = $this->task;
		return sprintf("{\"type\":%s, \"task\":%s}",
			json_encode($this->type),
			method_exists($ts, 'json_encode')? $ts->json_encode(): json_encode($ts)
		);
	}

	static public function json_decode ($json) {
		$obj = json_decode($json);
		$type = 0;
		$task = NULL;
		if (property_exists($obj, 'type'))
			$type = $obj->type;
		else
			throw new Exception("JSON object is missing 'type' property.", 1);
		if (property_exists($obj, 'task'))
			$task = new Task($obj->task);
		else
			throw new Exception("JSON object is missing 'task'.", 1);

		return new BRCTaskModification($task, $type);
	}
}