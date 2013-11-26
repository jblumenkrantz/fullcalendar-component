<?php
class Hallpass extends PinwheelModelObject
{
	public $hallpass_id;
	public $out_time;
	public $in_time;
	public $checked_in_by;
	public $pass_holder_user_id;
	public $pass_holder_first_name;
	public $pass_holder_last_name;
	public $destination_id;
	public $destination_name;
	public $authority_user_id;
	public $authority_first_name;
	public $authority_last_name;
	public $version;

	/**
	*	The parent construct call static hook Hallpass::defaults to assign
	*	initial values to Hallpass's properties.
	*/
	static protected function defaults () {
		return array (
			'hallpass_id' => NULL,
			'out_time'=>NULL,
			'in_time' => NULL,
			'checked_in_by' => NULL,
			'pass_holder_user_id' => NULL,
			'pass_holder_first_name' => NULL,
			'pass_holder_last_name' => NULL,
			'destination_id' => NULL,
			'destination_name' => NULL,
			'authority_user_id' => NULL,
			'authority_first_name' => NULL,
			'authority_last_name' => NULL,
			'version' => NULL
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
		return(static:: loadByQuery(
			"SELECT
				hallpass_id,
				UNIX_TIMESTAMP(out_time) as out_time,
				UNIX_TIMESTAMP(in_time) as in_time,
				pass_holder_user_id,
				pass_holder_last_name,
				pass_holder_first_name,
				destination_id,
				location_name as destination_name,
				authority_user_id,
				(select 
					concat(first_name,' ', last_name)
					FROM users 
					where user_id = checked_in_by
				) as checked_in_by,
				authority_first_name,
				authority_last_name,
				hallpass.version
			FROM hallpass
			LEFT OUTER JOIN locations
			ON hallpass.destination_id = locations.location_id
			LEFT OUTER JOIN users
			on authority_user_id = users.user_id
			WHERE hallpass_id = '$id'
			"
		, $pinsqli));
	}

	static public function loadActive ($pinsqli = NULL) {
		return(static:: loadByQuery(
			"SELECT
				hallpass_id,
				UNIX_TIMESTAMP(out_time) as out_time,
				UNIX_TIMESTAMP(in_time) as in_time,
				pass_holder_user_id,
				pass_holder_last_name,
				pass_holder_first_name,
				destination_id,
				location_name as destination_name,
				authority_user_id,
				(select 
					concat(first_name,' ', last_name)
					FROM users 
					where user_id = checked_in_by
				) as checked_in_by,
				authority_first_name,
				authority_last_name,
				hallpass.version
			FROM hallpass
			LEFT OUTER JOIN locations
			ON hallpass.destination_id = locations.location_id
			LEFT OUTER JOIN users
			on authority_user_id = users.user_id
			WHERE (in_time IS NULL OR in_time='')
			order by out_time DESC
			"
		, $pinsqli));
	}
	static public function loadAll ($pinsqli = NULL) {
		return(static:: loadByQuery(
			"SELECT
				hallpass_id,
				UNIX_TIMESTAMP(out_time) as out_time,
				UNIX_TIMESTAMP(in_time) as in_time,
				pass_holder_user_id,
				pass_holder_last_name,
				pass_holder_first_name,
				destination_id,
				location_name as destination_name,
				authority_user_id,
				(select 
					concat(first_name,' ', last_name)
					FROM users 
					where user_id = checked_in_by
				) as checked_in_by,
				authority_first_name,
				authority_last_name,
				hallpass.version
			FROM hallpass
			LEFT OUTER JOIN locations
			ON hallpass.destination_id = locations.location_id
			LEFT OUTER JOIN users
			on authority_user_id = users.user_id
			order by out_time DESC
			"
		, $pinsqli));
	}
	static public function loadOrgUsers ($org_id,$pinsqli = NULL) {
		return(static:: loadByQuery(
			"SELECT
				users.user_id, user_handle, first_name, last_name
				FROM users
				LEFT OUTER JOIN users_orgs
				ON users_orgs.user_id = users.user_id
				WHERE org_id = '$org_id'
				AND users.active = 1
			"
		, $pinsqli, true));
	}
	/**
	*	Hallpass::reload will 'refresh' $this Hallpass from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT
				hallpass_id,
				UNIX_TIMESTAMP(out_time) as out_time,
				UNIX_TIMESTAMP(in_time) as in_time,
				pass_holder_user_id,
				pass_holder_last_name,
				pass_holder_first_name,
				destination_id,
				location_name as destination_name,
				authority_user_id,
				(select 
					concat(first_name,' ', last_name)
					FROM users 
					where user_id = checked_in_by
				) as checked_in_by,
				authority_first_name,
				authority_last_name,
				hallpass.version
			FROM hallpass
			LEFT OUTER JOIN locations
			ON hallpass.destination_id = locations.location_id
			LEFT OUTER JOIN users
			on authority_user_id = users.user_id
				WHERE hallpass_id = '$this->hallpass_id'
			"
		);
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}

	static public function loadByQuery ($query, $pinsqli = NULL, $generic = false) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$data = array();
		if (!$pinsqli->errno) {
			while (($object = $resulti->fetch_object()))
			array_push($data, ($generic)? $object:new Hallpass($object));
		} else
			throw new Exception($pinsqli->error, 1);
		return $data;
	}

	static public function checkInPass($pass, $id){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE hallpass
				SET
					in_time = NOW(),
					checked_in_by = '$id',
					version 	= version + 1
				WHERE hallpass_id = '{$pass->hallpass_id}'
					AND version = '{$pass->version}'
			"
		);
		if (!$pinsqli->errno) {
			return static:: load($pass->hallpass_id);
		} else
			throw new Exception($pinsqli->error, 1);
	}

	static public function create($pass){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		// look up pass_holder
		$pass_holder = User::load($pass->pass_holder_user_id);
		$pass->pass_holder_first_name = $pass_holder->first_name;
		$pass->pass_holder_last_name = $pass_holder->last_name;
		$hallpassID = MySQLConnection::generateUID('hallpass');
		$resulti = $pinsqli->query(
			"INSERT 
				INTO hallpass
					(hallpass_id, out_time, pass_holder_user_id, pass_holder_first_name, pass_holder_last_name, destination_id, authority_user_id, authority_first_name, authority_last_name)
				values
					('$hallpassID', NOW(), '{$pass->pass_holder_user_id}', '{$pass->pass_holder_first_name}', '{$pass->pass_holder_last_name}', '{$pass->destination_id}', '{$pass->authority_user_id}', '{$pass->authority_first_name}', '{$pass->authority_last_name}')
			"
		);
		if (!$pinsqli->errno) {
			return static:: load($hallpassID);
		} else
			throw new Exception($pinsqli->error, 1);
	}

}
