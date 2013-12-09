<?php

class EventDataConflictException extends Exception
{
	static public $Errno = 409;
	static public $Message =
		'The request could not be completed due to a conflict with the current state of the Event resource.';

	private $resource;
	private $conflicts;
	public function __construct (Event $resource, array $conflicts) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->resource = $resource;
		$this->conflicts = $conflicts;
	}
	public function json_encode() {
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'resource'=>$this->resource,
			'conflicts'=>$this->conflicts
		));
	}
}