<?php

class ZoneActivity extends ZoneObject
{
	public $id;
	public $name;
	
	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array(
			'activityID' => 'id',
			'activity' => 'name'
		);
	}
}