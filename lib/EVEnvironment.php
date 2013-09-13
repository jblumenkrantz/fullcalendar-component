<?php

class EVEnvironment
{
	static private $_env = array();
	static public function set ($key, $value) {
		static::$_env [$key] = $value;
	}
	static public function get ($key) {
		$value = NULL;
		if (array_key_exists($key, static::$_env))
			$value = static::$_env [$key];
		else
			$value = getenv($key);
		return $value;
	}
	static public function json_encode() {
		return json_encode(static:: $_env);
	}

	static public $PRODUCTION = 'EVEnvironment::PRODUCTION';
	static public $DEVELOPMENT = 'EVEnvironment::DEVELOPMENT';
	static private $_deplomentTypeKey = 'EVEnvironment::_deplomentTypeKey';
	
	static public function setDeploymentType ($type) {
		static:: set(static:: $_deplomentTypeKey, $type);
	}

	static public function deploymentType () {
		return static:: get (static:: $_deplomentTypeKey);
	}

	static public function IN_PRODUCTION () {
		return static:: deploymentType() === static:: $PRODUCTION;
	}

	static public function IN_DEVELOPMENT () {
		return static:: deploymentType() === static:: $DEVELOPMENT;
	}
}