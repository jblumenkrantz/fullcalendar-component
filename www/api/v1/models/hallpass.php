<?php
class Hallpass extends PinwheelModelObject
{
	public $hallpass_id;
	public $out_time;
	public $in_time;
	public $pass_holder_user_id;
	public $pass_holder_first_name;
	public $pass_holder_last_name;
	public $destination;
	public $authority_user_id;
	public $authority_first_name;
	public $authority_last_name;

	/**
	*	The parent construct call static hook Hallpass::defaults to assign
	*	initial values to Hallpass's properties.
	*/
	static protected function defaults () {
		return array (
			'hallpass_id' => NULL,
			'out_time'=>NULL,
			'in_time' => NULL,
			'pass_holder_user_id' => NULL,
			'pass_holder_first_name' => NULL,
			'pass_holder_last_name' => NULL,
			'destination' => NULL,
			'authority_user_id' => NULL,
			'authority_first_name' => NULL,
			'authority_last_name' => NULL
		);
	}

	/**
	*	Hallpass::factory builds Hallpass from passed properties. Consider, Hallpass::factory
	*	does NOT consult the data-storage, see Hallpass::reload for 'refreshing' a Hallpass
	*	from datastore.
	*
	*	@param $p  Hallpass property array or Hallpass object.
	*	@return Hallpass object.
	*/
	static public function factory ($p) {
		error_log(print_r($p,true));
		return new Hallpass($p);
	}


	/**
	*	Hallpass::load builds Hallpass from datastore.
	*
	*	@param $id  Hallpass_id.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return Hallpass object.
	*/
	static public function load ($id, $pinsqli = NULL) {
		return(static:: loadWithQuery(
			"SELECT
				hallpass_id, out_time, in_time, pass_holder_user_id, pass_holder_first_name, pass_holder_last_name, destination, authority_user_id, authority_first_name, authority_last_name
				FROM hallpass
				WHERE hallpass_id = '$id'
			"
		, $pinsqli));
	}

	static public function loadActive ($pinsqli = NULL) {
		return(static:: loadWithQuery(
			"SELECT
				hallpass_id, out_time, in_time, pass_holder_user_id, pass_holder_first_name, pass_holder_last_name, destination, authority_user_id, authority_first_name, authority_last_name
				FROM hallpass
				WHERE in_time IS NULL
			"
		, $pinsqli));
	}
	
	/**
	*	Hallpass::reload will 'refresh' $this Hallpass from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
				hallpass_id, out_time, in_time, pass_holder_user_id, pass_holder_first_name, pass_holder_last_name, destination, authority_user_id, authority_first_name, authority_last_name
				FROM hallpass
				WHERE hallpass_id = '$this->hallpass_id'
			"
		);
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}

	static public function loadWithQuery ($query, $pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$data = array();
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				array_push($data,new Hallpass($object));
		} else
			throw new Exception($pinsqli->error, 1);
		return $data;
	}

}
