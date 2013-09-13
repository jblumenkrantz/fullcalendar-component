<?php

class AdminExistsException extends Exception
{
	static public $Errno = 406;
	static public $Message = "The user you are attempting to grant Admin or Super-Admin privileges already has those privileges";
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