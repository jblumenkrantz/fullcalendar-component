<?php

class ZoneObject
{
	/** defaults **/
	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array();
	}

	protected static function relations () {
		return array();
	}

	public function __construct ($properties) {
		$defaults = static:: defaults();
		foreach ($defaults as $key => $value) {
			if (property_exists($this, $key))
				$this->$key = $value;
		}
		if (gettype($properties) === 'object')
			$properties = get_object_vars($properties);
		if (gettype($properties) === 'array') {
			$renames = static:: renames();
			foreach ($renames as $fromName => $toName) {
				if (array_key_exists($fromName, $properties))
					$properties[$toName] = $properties[$fromName];
			}
			foreach ($properties as $key => $value) {
				if (property_exists($this, $key))
					$this->$key = $value;
			}
		}
		$relations = static:: relations();
		foreach ($relations as $key => $class)
			$this->$key = new $class($properties);
	}

	//** abstract api **//
	public function load () {
		throw new Exception(sprintf(
			"%s::load is not implemented",
			get_class($this)
		), 1);
	}

	public function create () {
		throw new Exception(sprintf(
			"%s::create is not implemented",
			get_class($this)
		), 1);
	}
	public function update () {
		throw new Exception(sprintf(
			"%s::update is not implemented",
			get_class($this)
		), 1);
	}
	public function delete () {
		throw new Exception(sprintf(
			"%s::delete is not implemented",
			get_class($this)
		), 1);
	}
}

