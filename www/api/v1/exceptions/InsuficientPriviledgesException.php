<?php

class InsuficientPriviledgesException extends Exception
{
	static public $Errno = 406;
	static public $Message = "This account does not have the nesecary access level to complete this action.  Please contact your orginizations Super-Administrator";
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