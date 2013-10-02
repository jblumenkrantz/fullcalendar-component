<?php

class TaskDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "Requested Task resource does not exist.";
	private $task;
	public function __construct (Task $task) {
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