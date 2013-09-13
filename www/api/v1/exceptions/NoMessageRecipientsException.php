<?php

class NoMessageRecipientsException extends Exception
{
	static public $Errno = 406;
	static public $Message = "You have not provided any recipients for your message, please select some and try again.";
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