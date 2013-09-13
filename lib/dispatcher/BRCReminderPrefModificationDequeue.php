<?php

class BRCReminderPrefModificationDequeue extends BRCReminderPrefModification
{
	static public $Topic = 'com.schooldatebooks.brc.BRCReminderPrefModificationDequeue';
	protected $receipt;

	public function __construct ($message, $receipt) {
		$modification = BRCReminderPrefModification:: json_decode($message);
		parent:: __construct($modification->reminderPreference, $modification->type);
		$this->receipt = $receipt;
	}

	public function receipt () {
		return $this->receipt;
	}

	public function __get ($property) {
		if (property_exists($this, $property) && method_exists($this, $property))
			return call_user_func(array($this, $property));
		return parent::__get($property);
	}

	static public function changeVisibility ($rprefmodDq, $visiblity, $queue = NULL) {
		if (gettype($rprefmodDq) !== 'array')
			$rprefmodDq = array($rprefmodDq);
		$receipts = array();
		foreach ($rprefmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->changeMessagesVisiblity($receipts, $visblility);
	}

	static public function delete ($rprefmodDq, $queue = NULL) {
		if (gettype($rprefmodDq) !== 'array')
			$rprefmodDq = array($rprefmodDq);
		$receipts = array();
		foreach ($rprefmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->deleteMessages($receipts);
	}
}