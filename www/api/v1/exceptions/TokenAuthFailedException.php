<?php

class TokenAuthFailedException extends Exception
{
	static public $Errno = 400;
	static public $Message = "Authorization token is malformed.";
	public function __construct () {
		parent:: __construct(static:: $Message, static:: $Errno);
	}
	public function json_encode() {
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
		));
	}
}