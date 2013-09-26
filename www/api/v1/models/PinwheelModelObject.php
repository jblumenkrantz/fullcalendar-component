<?php

class PinwheelModelObject
{
	static protected function defaults () {
		return array();
	}

	public function __construct ($properties=null) {
		$defaults = static:: defaults();
		if (is_object($properties))
			$properties = get_object_vars($properties);

		if (is_array($properties)) {
			$properties = array_merge($defaults, $properties);
			foreach ($properties as $key => $value) {
				if (property_exists($this, $key))
					$this->$key = $value;
			}
		}

		if(method_exists($this, 'init')){
			$this->init($properties);
		}

	}

	public function setProperties ($properties) {
		$defaults = get_object_vars($this);
		if (is_object($properties))
			$properties = get_object_vars($properties);
		if (is_array($properties)) {
			$properties = array_merge($defaults, $properties);
			foreach ($properties as $key => $value) {
				if (property_exists($this, $key))
					$this->$key = $value;
			}
		}
	}

	static public function genericQuery($query, $pinsqli=NULL){
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$result = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
				array_push($result, $object);
		} else
			throw new Exception($pinsqli->error, 1);

		return $result;
	}
	static public function object_to_array($obj) 
	{
		$arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
		foreach ($arrObj as $key => $val) {
			$val = (is_array($val) || is_object($val)) ? static:: object_to_array($val) : $val;
			$arr[$key] = $val;
		}
		return $arr;
	}
	static public function mysql_escape_array($obj, $pinsqli=NULL) 
	{
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
		foreach ($arrObj as $key => $val) {
			$val = (is_array($val) || is_object($val)) ? static:: object_to_array($val) :  $pinsqli->real_escape_string($val);
			$arr[$key] =$val;
		}
		return $arr;
	}
}
