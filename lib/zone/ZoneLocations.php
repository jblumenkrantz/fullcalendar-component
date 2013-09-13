<?php

class ZoneLocation extends ZoneObject
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
			'locationID' => 'id',
			'location' => 'name',
			'locationAddress' => 'address',
			'locationCity' => 'city',
			'locationState' => 'state',
			'locationZip' => 'zip'
		);
	}
}