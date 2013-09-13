<?php

class BRCTaskModificationDequeue extends BRCTaskModification
{
	static public $Topic = 'com.schooldatebooks.brc.BRCTaskModificationDequeue';
	protected $receipt;

	public function __construct ($message, $receipt) {
		$modification = BRCTaskModification:: json_decode($message);
		parent:: __construct($modification->task, $modification->type);
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

	static public function changeVisibility ($taskmodDq, $visiblity, $queue = NULL) {
		if (gettype($taskmodDq) !== 'array')
			$taskmodDq = array($taskmodDq);
		$receipts = array();
		foreach ($taskmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->changeMessagesVisiblity($receipts, $visblility);
	}

	static public function delete ($taskmodDq, $queue = NULL) {
		if (gettype($taskmodDq) !== 'array')
			$taskmodDq = array($taskmodDq);
		$receipts = array();
		foreach ($taskmodDq as $mod)
			array_push($receipts, array('receipt'=>$mod->receipt));
		$queue = $queue? $queue: BRCReminderModificationQueue:: sharedInstance();
		$queue->deleteMessages($receipts);
	}
}