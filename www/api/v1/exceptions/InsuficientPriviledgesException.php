<?php

class InsuficientPriviledgesException extends Exception
{
	static public $Errno = 406;
	static public $Message = "This account does not have the nesecary access level to complete this action.  Please contact your orginizations Super-Administrator";
	private $authType;
	private $realm;
	private $location;
	public function __construct ($authType = '', $realm = '', $location = '') {
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