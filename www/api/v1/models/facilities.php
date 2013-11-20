<?php
class Facilities extends PinwheelModelObject
{
	public $location_id;
	public $location_name;
	public $building_id;
	public $room_number;
	public $room_id;
	public $org_id;
	public $addr_1;
	public $addr_2;
	public $city;
	public $state;
	public $zip;
	public $lat;
	public $lon;

	/**
	*	The parent construct call static hook Facilities::defaults to assign
	*	initial values to Facilities's properties.
	*/
	static protected function defaults () {
		return array (
			'location_id' => NULL,
			'location_name'=>NULL,
			'building_id' => NULL,
			'room_number' => NULL,
			'room_id' => NULL,
			'org_id' => NULL,
			'addr_1' => NULL,
			'addr_2' => NULL,
			'city' => NULL,
			'state' => NULL,
			'zip' => NULL,
			'lat' => NULL,
			'lon' => NULL,
		);
	}

	/**
	*	Facilities::factory builds Facilities from passed properties. Consider, Facilities::factory
	*	does NOT consult the data-storage, see Facilities::reload for 'refreshing' a Facilities
	*	from datastore.
	*
	*	@param $p  Facilities property array or Facilities object.
	*	@return Facilities object.
	*/
	static public function factory ($p) {
		error_log(print_r($p,true));
		return new Facilities($p);
	}


	/**
	*	Facilities::load builds Facilities from datastore.
	*
	*	@param $id  Facilities_id.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Facilities object.
	*/
	static public function load ($id, $pinsqli = NULL) {
		return(static:: loadByQuery(
			"SELECT
				location_id, location_name, building_id, room_number, org_id, addr_1, addr_2, city, state, zip, lat, lon
				FROM locations
				WHERE org_id = '$id'
			"
		, $pinsqli));
	}
	
	/**
	*	Facilities::reload will 'refresh' $this Facilities from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
				location_id, location_name, building_id, room_number, org_id, addr_1, addr_2, city, state, zip, lat, lon
				FROM locations
				WHERE location_id = '$this->location_id'
			"
		);
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}

	static public function loadByQuery ($query, $pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$data = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
			array_push($data, new Facilities($object));
		} else
			throw new Exception($pinsqli->error, 1);
		return $data;
	}

}
