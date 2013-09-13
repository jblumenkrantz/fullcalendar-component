<?php

class BRCReminderPrefModification extends Symfony\Component\EventDispatcher\Event
{
	static public $Topic = 'com.schooldatebooks.brc.BRCReminderPrefModification';
	static public $Created = 'com.schooldatebooks.brc.BRCReminderPrefModification::Created';
	static public $Updated = 'com.schooldatebooks.brc.BRCReminderPrefModification::Updated';
	static public $Deleted = 'com.schooldatebooks.brc.BRCReminderPrefModification::Deleted';
	protected $reminderPreference;
	protected $type;

	public function __construct (ReminderPrefs $rpref, $type) {
		$this->reminderPreference = $rpref;
		$this->type = $type;
	}

	public function reminderPreference () {
		return $this->reminderPreference;
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
		$rpref = $this->reminderPreference;
		return sprintf("{\"type\":%s, \"reminderPreference\":%s}",
			json_encode($this->type),
			method_exists($rpref, 'json_encode')? $rpref->json_encode(): json_encode($rpref)
		);
	}

	static public function json_decode ($json) {
		$obj = json_decode($json);
		$type = 0;
		$rpref = NULL;
		if (property_exists($obj, 'type'))
			$type = $obj->type;
		else
			throw new Exception("JSON object is missing 'type' property.", 1);
		if (property_exists($obj, 'reminderPreference'))
			$rpref = new ReminderPrefs($obj->reminderPreference);
		else
			throw new Exception("JSON object is missing 'reminderPreference'.", 1);
		return new BRCReminderPrefModification($rpref, $type);
	}
}