<?php

class ZoneLevel extends ZoneObject
{
	public $id;
	public $name;
	
	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array(
			'levelID' => 'id',
			'levelName' => 'name'
		);
	}
}