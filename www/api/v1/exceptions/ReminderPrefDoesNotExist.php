<?php

class ReminderPrefDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "Requested ReminderPref resource does not exist.";
	private $rpref;
	public function __construct (ReminderPrefs $rpref) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->rpref = $rpref;
	}
	public function json_encode() {
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'request'=>$this->rpref
		));
	}
}