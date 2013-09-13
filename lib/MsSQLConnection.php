<?php

class MsSQLConnection
{
	static public $NamesKey = 'MsSQLConnection::NamesKey';
	static public $DefaultName = 'MsSQLConnection::DefaultName';
	static public $ServerKey = 'MsSQLConnection::SQLServerKey';
	static public $UsernameKey = 'MsSQLConnection::SQLUsernameKey';
	static public $PasswordKey = 'MsSQLConnection::SQLPasswordKey';
	static public $DatabaseKey = 'MsSQLConnection::SQLDatabaseKey';
	static private $_connections = array();


	/**
	*	@param $name the name of the connection (optional)
	*	@return MSSQL reference handle
	*/
	static public function connect ($name = NULL) {
		if ($name === NULL) $name = MsSQLConnection:: $DefaultName;
		if (!array_key_exists($name, MsSQLConnection:: $_connections)) {
			$connection = MsSQLConnection:: factoryWithName($name);
			if ($connection) {
				mssql_query('SET ANSI_WARNINGS ON');
				mssql_query('SET ANSI_NULLS ON');
			}
			MsSQLConnection:: $_connections[$name] = $connection;
		}
		return MsSQLConnection:: $_connections[$name];
	}


	/**
	*	@param $name the name of the connection (optional)
	*/
	static public function close ($name = NULL) {
		if ($name === NULL) $name = MsSQLConnection:: $DefaultName;
		if (array_key_exists($name, MsSQLConnection:: $_connections)) {
			$link = MsSQLConnection:: $_connections[$name];
			if ($link !== NULL) {
				mssql_close($link);
				unset(MsSQLConnection:: $_connections[$name]);
			}
		}
	}


	/**
	*	@param $server mysql database server. (optional)
	*	@param $username mysql account username. (optional)
	*	@param $password mysql account password. (optional)
	*	@param $database database to use upon connecting to server. (optional)
	*	@return instance of MySQLConnection
	*/
	static public function factoryWithParameters ($server = NULL, $username = NULL, $password = NULL, $database = NULL) {
		$server = $server? $server : EVEnvironment:: get(MsSQLConnection:: $ServerKey);
		$username = $username? $username : EVEnvironment:: get(MsSQLConnection:: $UsernameKey);
		$password = $password? $password : EVEnvironment:: get(MsSQLConnection:: $PasswordKey);
		$database = $database? $database : EVEnvironment:: get(MsSQLConnection:: $DatabaseKey);
		$link = mssql_connect($server, $username, $password);
		if (!mssql_select_db($database))
			$link = NULL;
		return $link;
	}


	/**
	*	@param $name connection name; the name is used to establish params from env. (optional)
	*	@return instance of MySQLConnection
	*/
	static public function factoryWithName ($name = NULL) {
		if ($name === NULL) $name = MsSQLConnection:: $DefaultName;
		$names = EVEnvironment:: get(MsSQLConnection:: $NamesKey);
		$descp = gettype($names) === 'array' && array_key_exists($name, $names)? $names[$name]: NULL;
		if (
				gettype($descp) === 'array' &&
				array_key_exists(MsSQLConnection:: $ServerKey, $descp) &&
				array_key_exists(MsSQLConnection:: $UsernameKey, $descp) &&
				array_key_exists(MsSQLConnection:: $PasswordKey, $descp) &&
				array_key_exists(MsSQLConnection:: $DatabaseKey, $descp)
		) {
			return MsSQLConnection:: factoryWithParameters(
				$descp[MsSQLConnection:: $ServerKey],
				$descp[MsSQLConnection:: $UsernameKey],
				$descp[MsSQLConnection:: $PasswordKey],
				$descp[MsSQLConnection:: $DatabaseKey]
			);
		}
		return NULL;
	}


	/**
	*	Prints the list of tables for the selected database.
	*/
	static public function test_listTables () {
		$zonesql = MsSQLConnection:: connect('Zone');
		$sdisql = MsSQLConnection:: connect('SDIMain');
		$zoneresult = mssql_query('sp_tables', $zonesql);
		if ($zoneresult) {
			echo '<p><h1>Zone Tables</h1><ol>';
			while (($object = mssql_fetch_object($zoneresult))) {
				$tableName = $object->TABLE_NAME;
				echo "<li>$tableName</li>";
			}
			echo '</ol></p>';
			mssql_free_result($zoneresult);
		}
		$sdiresult = mssql_query('sp_tables', $sdisql);
		if ($sdiresult) {
			echo '<p><h1>SDIMain Tables</h1><ol>';
			while (($object = mssql_fetch_object($sdiresult))) {
				$tableName = $object->TABLE_NAME;
				echo "<li>$tableName</li>";
			}
			echo '</ol></p>';
			mssql_free_result($sdiresult);
		}
		MsSQLConnection:: close('Zone');
		MsSQLConnection:: close('SDIMain');
	}
}

