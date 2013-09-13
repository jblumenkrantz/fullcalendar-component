<?php

class CantFindUser extends Exception
{
	static public $Errno = 406;
	static public $Message = "The provided user information could not be found.  Please try again.";
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