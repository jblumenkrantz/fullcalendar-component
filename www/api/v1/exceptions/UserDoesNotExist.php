<?php

class UserDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "User does not exist.";
	public function __construct () {
		parent:: __construct(static:: $Message, static:: $Errno);
	}
	public function json_encode() {
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage()
		));
	}
}