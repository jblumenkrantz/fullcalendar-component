<?php

class UserForbiddenException extends Exception
{
	static public $Errno = 403;
	static public $Message = "The requested resource is forbidden. The user does not have permission to access the resource.";
	private $userID;
	private $request;
	public function __construct ($userID, $request = NULL) {
		parent:: __construct(static:: $Message, static:: $Errno);
		$this->userID = $userID;
		$this->request = $request;
	}
	public function json_encode() {
		$pkg = array(
			'errno'=>$this->getCode(),
			'message'=>$this->getMessage(),
			'authUserID'=>$this->userID
		);
		if ($this->request !== NULL)
			$pkg['requested'] = $this->request;
		return json_encode($pkg);
	}
}