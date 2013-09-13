<?php

class InvalidShareTokenException extends Exception
{
	static public $Errno = 406;
	static public $Message = "The submitted share invitation is either invalid or it has already been used.";
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