<?php

class ZoneOpponent extends ZoneObject
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
			'opponentID' => 'id',
			'opponent' => 'name',
			'opponentAddress' => 'address',
			'opponentCity' => 'city',
			'opponentState' => 'state',
			'opponentZip' => 'zip'
		);
	}
}