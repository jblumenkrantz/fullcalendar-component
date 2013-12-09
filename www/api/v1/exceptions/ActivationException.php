<?php

class ActivationException extends Exception
{
	static public $Errno = 408;
	static public $Message = "Contact point activation failure.  Either the submitted activation code was incorrect or the user does not have permission to activate that contact point.";
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
		header("HTTP/1.0 {$this->getCode()} {$this->getMessage()}");
		return json_encode($pkg);

	}
}