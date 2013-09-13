<?php

class ZoneSchool extends ZoneObject
{
	public $id;
	public $name;
	public $address;
	public $city;
	public $state;
	public $zip;
	
	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array(
			'zoneID' => 'id',
			'SDIName' => 'name',
		);
	}
}