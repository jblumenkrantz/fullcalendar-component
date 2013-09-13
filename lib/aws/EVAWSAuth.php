<?php

class EVAWSAuth
{
	static public $KeyName = 'AWSKey';
	static public $SecretName = 'AWSSecret';
	static private $_key = NULL;
	static private $_secret = NULL;
	protected function getKey () {
		$key = EVAWSAuth:: $_key;
		if ($key === NULL)
			$key = EVEnvironment:: get(EVAWSAuth:: $KeyName);
		return $key;
	}
	protected function getSecret () {
		$secret = EVAWSAuth:: $_secret;
		if ($secret === NULL)
			$secret = EVEnvironment:: get(EVAWSAuth:: $SecretName);
		return $secret;
	}
	static public function setAuthorization ($key, $secret) {
		EVAWSAuth:: $_key = $key;
		EVAWSAuth:: $_secret = $secret;
	} 
}
