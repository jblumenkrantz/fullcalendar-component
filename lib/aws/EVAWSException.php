<?php

class EVAWSException extends Exception
{
	static public $EVAWSExceptionCode = -1;
	private $_isAWSError;
	private $_AWSCode;

	public function __construct ($AWSError) {
		if ($AWSError instanceof CFSimpleXML) {
			$message = $AWSError->Message->to_array();
			$message = $message [0];
			$this->_AWSCode = $AWSError->Code->to_array();
			$this->_isAWSError = true;
			parent::__construct($message, EVAWSException:: $EVAWSExceptionCode);
		} else {
			parent::__construct($AWSError, EVAWSException:: $EVAWSExceptionCode);
			$this->_isAWSError = false;
		}
	}
}