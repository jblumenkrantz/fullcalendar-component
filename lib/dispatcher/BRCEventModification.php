<?php

class BRCEventModification extends Symfony\Component\EventDispatcher\Event
{
	static public $Topic = 'com.schooldatebooks.brc.BRCEventModification';
	static public $Created = 'com.schooldatebooks.brc.BRCEventModification::Created';
	static public $Updated = 'com.schooldatebooks.brc.BRCEventModification::Updated';
	static public $Deleted = 'com.schooldatebooks.brc.BRCEventModification::Deleted';
	protected $event;
	protected $type;

	public function __construct (Event $event, $type) {
		$this->event = $event;
		$this->type = $type;
	}

	public function event () {
		return $this->event;
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
		$ev = $this->event;
		return sprintf("{\"type\":%s, \"event\":%s}",
			json_encode($this->type),
			method_exists($ev, 'json_encode')? $ev->json_encode(): json_encode($ev)
		);
	}

	static public function json_decode($json) {
		$obj = json_decode($json);
		$type = 0;
		$event = NULL;
		if (property_exists($obj, 'type'))
			$type = $obj->type;
		else
			throw new Exception("JSON object is missing 'type' property.", 1);
		if (property_exists($obj, 'event'))
			$event = new Event($obj->event);
		else
			throw new Exception("JSON object is missing 'event'.", 1);

		return new BRCEventModification($event, $type);
	}
}