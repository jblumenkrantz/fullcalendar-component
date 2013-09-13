<?php

class MySQLConnection extends mysqli
{
	static public $NamesKey = 'MySQLConnection::NamesKey';
	static public $DefaultName = 'MySQLConnection::DefaultName';
	static public $ServerKey = 'MySQLConnection::ServerKey';
	static public $UsernameKey = 'MySQLConnection::UsernameKey';
	static public $PasswordKey = 'MySQLConnection::PasswordKey';
	static public $DatabaseKey = 'MySQLConnection::DatabaseKey';
	static public $PortKey = 'MySQLConnection::PortKey';


	/**
	*	@param $name the name of the connection (optional)
	*	@return singleton instance of MySQLConnection with default settings.
	*/
	static public function sharedInstance ($name = NULL) {
		static $instances = array();
		if ($name === NULL) $name = static:: $DefaultName;
		if (!array_key_exists($name, $instances)) {
			$instance = static:: factoryWithName($name);
			if ($instance !== NULL)
				mysqli_set_charset($instance, 'utf8');
			$instances[$name] = $instance;
		}
		return $instances[$name];
	}


	/**
	*	Class factory method for building instances of MySQLConnection. The parameters
	*	are optional, and if a parameter is not provided, the missing paramater will
	*	take on the default value from the Environment.
	*
	*	@param $server mysql database server. (optional)
	*	@param $username mysql account username. (optional)
	*	@param $password mysql account password. (optional)
	*	@param $database database to use upon connecting to server. (optional)
	*	@return instance of MySQLConnection
	*/
	static public function factoryWithParameters ($server = NULL, $username = NULL, $password = NULL, $database = NULL, $port = NULL) {
		$server = $server? $server : EVEnvironment:: get(static:: $ServerKey);
		$username = $username? $username : EVEnvironment:: get(static:: $UsernameKey);
		$password = $password? $password : EVEnvironment:: get(static:: $PasswordKey);
		$database = $database? $database : EVEnvironment:: get(static:: $DatabaseKey);
		$port = $port? $port : EVEnvironment:: get(static:: $PortKey);
		$me = NULL;
		$me = new static($server, $username, $password, $database, $port);
		if ($me->connect_errno)
			throw new Exception($me->connect_error, $me->connect_errno);
		return $me;
	}


	/**
	*	Class factory method for building instances of MySQLConnection. The name
	*	is optional, and if a name is not provided, the name will
	*	take on the default, MySQLConnection::DefaultName.
	*
	*	@param $name connection name; the name is used to establish params from env. (optional)
	*	@return instance of MySQLConnection
	*/
	static public function factoryWithName ($name = NULL) {
		if ($name === NULL) $name = static:: $DefaultName;
		$names = EVEnvironment:: get(static:: $NamesKey);
		$descp = gettype($names) === 'array' && array_key_exists($name, $names)? $names[$name]: NULL;
		if (
				gettype($descp) === 'array' &&
				array_key_exists(static:: $ServerKey, $descp) &&
				array_key_exists(static:: $UsernameKey, $descp) &&
				array_key_exists(static:: $PasswordKey, $descp) &&
				array_key_exists(static:: $DatabaseKey, $descp) &&
				array_key_exists(static:: $PortKey, $descp)
		) {
			return static:: factoryWithParameters(
				$descp[static:: $ServerKey],
				$descp[static:: $UsernameKey],
				$descp[static:: $PasswordKey],
				$descp[static:: $DatabaseKey],
				$descp[static:: $PortKey]
			);
		}
		return NULL;
	}


	/**
	*	Prints the list of tables for the selected database.
	*/
	static public function test_listTables () {
		$pinsqli = static:: sharedInstance();
		$resulti = $pinsqli->query("show tables");
		if (!$pinsqli->errno) {
			echo '<p><h1>BRC Tables</h1><ol>';
			while (($object = $resulti->fetch_object())) {
				if (property_exists($object, 'Tables_in_brc'))
					echo '<li>'.$object->Tables_in_brc.'</li>';
			}
			echo '</ol></p>';
		}
		else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	Custom UID, but not UUID.
	*
	*	@param $name suffix for the UID.
	*	@return custom UID
	*/
	static public function generateUID($name) {
		$now = explode(" ", microtime());
		return $name . "_" . date("YmdHis", $now[1]) . str_replace(".", "x", $now[0]) . "x" . mt_rand(0, 9999999);
	}

	static public function generateActivationCode($length = 7) {
		$characters = '2346789abcdefghjkmnpqrtuvwxyz';
		$activationCode = '';
		for ($i = 0; $i < $length; $i++) {
			$activationCode .= $characters[rand(0, strlen($characters) - 1)];
		}
		return strtoupper($activationCode);
	}

	/**
	*	UUID V4.
	*	* Conforms to RFC 4122 <http://www.ietf.org/rfc/rfc4122.txt>
	*	** Implementation found here http://php.net/manual/en/function.uniqid.php#94959
	*
	*	@param $name suffix for the UID.
	*	@return custom UID
	*/
	static public function generateUUID4($name) {
		return sprintf('%s_%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			$name,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
	    );
	}
}

