<?php

class UserDeactivatedException extends Exception
{
	static public $Errno = 403;
	static public $Message = "User account has been deactivated.";
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