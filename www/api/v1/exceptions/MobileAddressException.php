<?php

class MobileAddressException extends Exception
{
	static public $Errno = 407;
	static public $Message = "Mobile phone address could not be validated.  The submitted phone number was either incorrect or malformed";
	private $request;
	public function __construct ($request = NULL) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->request = $request;
	}
	public function json_encode() {
		$pkg = array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage()
		);
		if ($this->request !== NULL)
			$pkg['requested'] = $this->request;
		error_log(print_r($Errno,true));
		return json_encode($pkg);
	}
}