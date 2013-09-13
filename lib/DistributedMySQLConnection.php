<?php

class DistributedMySQLConnection extends MySQLConnection
{
	static public $ReadGroupKey = 'DistributedMySQLConnection::ReadGroupKey';
	static public $WriteGroupKey = 'DistributedMySQLConnection::WriteGroupKey';

	static public function readInstance () {
		$readGroups = EVEnvironment:: get(static:: $ReadGroupKey);
		if ($readGroups === null || !is_array($readGroups))
			throw new Exception("Please provide read group configuration (of type array).", 1);
		if (count($readGroups) == 0)
			throw new Exception("Cannot connect to MySQL Server(s) in read group configuration.", 1);
		$index = mt_rand(0,count($readGroups) - 1);
		$readInstanceName = $readGroups[$index];
		$me = NULL;
		try {
			$me = parent:: sharedInstance($readInstanceName);
		} catch (Exception $e) {
			unset($readGroups[$index]);
			$readGroups = array_values($readGroups);
			EVEnvironment:: set(static:: $ReadGroupKey, $readGroups);
			return static:: readInstance();
		}
		return $me;
	}

	static public function writeInstance () {
		static $writeInstanceName = NULL;
		if ($writeInstanceName === NULL) {
			$writeGroups = EVEnvironment:: get(static:: $WriteGroupKey);
			if ($writeGroups === null || !is_array($writeGroups))
				throw new Exception("Please provide write group configuration (of type array).", 1);
			if (count($writeGroups) == 0)
				throw new Exception("Cannot connect to MySQL Server(s) in write group configuration.", 1);
			$index = mt_rand(0,count($writeGroups) - 1);
			$writeInstanceName = $writeGroups[$index];
		}
		try {
			$me = parent:: sharedInstance($writeInstanceName);
		} catch (Exception $e) {
			$writeInstanceName = NULL;
			unset($writeGroups[$index]);
			$writeGroups = array_values($writeGroups);
			EVEnvironment:: set(static:: $WriteGroupKey, $writeGroups);
			return static:: writeInstance();
		}
		return $me;
	}
}
