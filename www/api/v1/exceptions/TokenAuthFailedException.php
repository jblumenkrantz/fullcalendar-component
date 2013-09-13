<?php

class TokenAuthFailedException extends Exception
{
	static public $Errno = 400;
	static public $Message = "Authorization token is malformed.";
	public function __construct () {
		parent:: __construct(static:: $Message, static:: $Errno);
	}
	public function json_encode() {
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
		));
	}
}