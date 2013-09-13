<?php

class EventDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "Requested Event resource does not exist.";
	private $event;
	public function __construct (Event $event) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->event = $event;
	}
	public function json_encode() {
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'request'=>$this->event
		));
	}
}