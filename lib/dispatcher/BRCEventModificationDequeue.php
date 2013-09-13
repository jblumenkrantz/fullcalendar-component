<?php

class BRCEventModificationDequeue extends BRCEventModification
{
	static public $Topic = 'com.schooldatebooks.brc.BRCEventModificationDequeue';
	protected $receipt;

	public function __construct ($message, $receipt) {
		$modification = BRCEventModification:: json_decode($message);
		parent:: __construct($modification->event, $modification->type);
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

	static public function changeVisibility ($evmodDq, $visblility, $queue = NULL) {
		if (gettype($evmodDq) !== 'array')
			$evmodDq = array($evmodDq);
		$receipts = array();
		foreach ($evmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->changeMessagesVisiblity($receipts, $visblility);
	}

	static public function delete ($evmodDq, $queue = NULL) {
		if (gettype($evmodDq) !== 'array')
			$evmodDq = array($evmodDq);
		$receipts = array();
		foreach ($evmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->deleteMessages($receipts);
	}
}