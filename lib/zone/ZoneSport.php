<?php

class ZoneSport extends ZoneObject
{
	public $id;
	public $name;
	
	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array(
			'sportID' => 'id',
			'sport' => 'name'
		);
	}
}