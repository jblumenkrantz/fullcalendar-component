<?php

class NoCredentialsProvidedException extends Exception
{
	static public $Errno = 400;
	static public $Message = "Authentication requires prior established authorization token or <user,password> pair.";
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
		return json_encode(array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'authType'=>$this->authType,
			'realm'=>$this->realm,
			'location'=>$this->location
		));
	}
}