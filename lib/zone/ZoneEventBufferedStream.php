<?php

class ZoneEventBufferedStream
{
	static private $_kDefaultBatchSize = 100;
	private $_zoneresult;
	private $_timezoneName;
	private $_utcOffset;

	public function __construct ($zoneID, $infimumTS, $utcOffset, $batchSize = FALSE) {
		$this->_zoneresult = NULL;
		$this->performEventQuery($zoneID, $infimumTS, $utcOffset, $batchSize);
	}

	protected function utcOffsetForZoneID ($zoneID) {
		$utcOffset = 0;
		$sdisql = MsSQLConnection:: connect ('SDIMain');
		if ($sdisql) {
			$sdiresult = mssql_query(
				"SELECT TimeZone.UTCOffset
					FROM Zone_Customers
					JOIN School ON School.SchoolID = Zone_Customers.CCID
					JOIN TimeZone ON School.TimeZoneID = TimeZone.TimeZoneID
					WHERE Zone_Customers.zoneID = $zoneID
				",
				$sdisql
			);
			if ($sdiresult === TRUE || $sdiresult === FALSE)
				throw new Exception("Failed to fetch timezone for '$zoneID'", 1);
			if (($row = mssql_fetch_assoc($sdiresult)) && array_key_exists('UTCOffset', $row)) {
				$utcOffset = $row['UTCOffset'] * 3600;
			}
			else
				throw new Exception("Failed to fetch timezone for '$zoneID'", 1);
		}
		return $utcOffset;
	}

	protected function performEventQuery ($zoneID, $infimumTS = 0, $utcOffset = FALSE, $batchSize = FALSE) {
		if ($batchSize === FALSE)
			$batchSize = ZoneEventBufferedStream:: $_kDefaultBatchSize;
		if ($utcOffset === FALSE)
			$utcOffset = $this->utcOffsetForZoneID($zoneID);
		$this->_utcOffset = $utcOffset;

		$zonesql = MsSQLConnection:: connect('Zone');
		if ($zonesql) {
			// Date String for Timestamp in Target Timzone
			$this->_timezoneName = timezone_name_from_abbr('', $utcOffset, 0);
			$timezone = new DateTimeZone($this->_timezoneName);
			$dateTime = new DateTime(NULL, $timezone);
			$dateTime->setTimestamp($infimumTS);
			$date = $dateTime->format('Y-m-d H:i:s');

			$zoneresult = mssql_query(
				"SELECT
					/* Event */
						e.eventID,
						e.eventDate,
						e.startTime,
						e.endTime,
						e.event,
					/* School */
						e.zoneID,
						c.SDIName,
						c.address,
						c.city,
						c.state,
						c.zip,
					/* Level */
						l.levelID,
						l.levelName,
					/* Sport */
						s.sportID,
						s.sport,
					/* Activity */
						a.activityID,
						a.activity,
					/* Opponent */
						o.opponentID,
						o.opponent,
						o.opponentAddress,
						o.opponentCity,
						o.opponentState,
						o.opponentZip,
					/* Location */
						loc.locationID,
						loc.location,
						loc.locationAddress,
						loc.locationCity,
						loc.locationState,
						loc.locationZip
					FROM Events e
					LEFT JOIN Levels l ON l.levelID = e.levelID
					LEFT JOIN Sports s ON s.sportID = e.sportID
					LEFT JOIN Activities a ON a.activityID = e.activityID
					LEFT JOIN Locations loc ON loc.locationID = e.activityID
					LEFT JOIN Opponents o ON o.opponentID = e.opponentID
					LEFT JOIN Customers c ON c.zoneID = e.zoneID
					WHERE e.zoneID IN ($zoneID)
						AND e.eventDate > '$date'
						AND ISNULL(e.isDeleted, 0) = 0
						AND e.eventStatus != 1 OR e.eventStatus IS NULL
					ORDER BY e.eventDate
				",
				$zonesql, $batchSize
			);
			if ($zoneresult)
				$this->_zoneresult = $zoneresult;
		}
	}

	protected function conditionEvent ($ev) {
		$norm = ZoneEvent:: normalizeTemporal($ev->eventDate, $ev->startTime, $ev->endTime, $this->_timezoneName);
		$ev->endTimeTS = array_pop($norm);
		$ev->startTimeTS = array_pop($norm);
		$ev->eventDateTS = array_pop($norm);
		return $ev;
	}

	public function readEvent () {
		if ($this->_zoneresult) {
			$object = mssql_fetch_object($this->_zoneresult);
			if (!$object && ($size = mssql_fetch_batch($this->_zoneresult))) {
				$object = mssql_fetch_object($this->_zoneresult);
			}
			if (!$object) {
				mssql_free_result($this->_zoneresult);
				$this->_zoneresult = NULL;
			}
			else
				$object = $this->conditionEvent($object);
			return $object;
		}
	}

	public function close () {
		$this->_zoneresult = NULL;
	}

	public function timezoneName () {
		return $this->_timezoneName;
	}

	public function utcOffset () {
		return $this->_utcOffset;
	}
}

