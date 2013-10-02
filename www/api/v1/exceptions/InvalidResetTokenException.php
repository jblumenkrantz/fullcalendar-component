<?php

class InvalidResetTokenException extends Exception
{
	static public $Errno = 406;
	static public $Message = "The password reset token provided is not valid or has expired.  If you have completed the password reset process more than once, make sure you are using the token from your most recent email.  You may need to complete the forgot password process again to successfully change your password.";
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
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode($pkg);
	}
}