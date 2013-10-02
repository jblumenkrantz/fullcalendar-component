<?php

class UserPassAuthFailedException extends Exception
{
	static public $Errno = 401;
	static public $Message = "User or password could not be authenticated.";
	private $authType;
	private $realm;
	private $location;
	public function __construct ($authType = '', $realm = '', $location = '') {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->authType = $authType;
		$this->realm = $realm;
		$this->location = $location;
	}
	public function json_encode() {
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'authType'=>$this->authType,
			'realm'=>$this->realm,
			'location'=>$this->location
		));
	}
}