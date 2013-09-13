<?php

class UserDoesNotExist extends Exception
{
	static public $Errno = 404;
	static public $Message = "User does not exist.";
	public function __construct () {
		parent:: __construct(static:: $Message, static:: $Errno);
	}
	public function json_encode() {
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage()
		));
	}
}