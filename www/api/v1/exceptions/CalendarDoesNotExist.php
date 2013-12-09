<?php

class CalendarDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "Requested Calendar resource does not exist.";
	private $task;
	public function __construct (Calendar $task) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->task = $task;
	}
	public function json_encode() {
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'request'=>$this->task
		));
	}
}