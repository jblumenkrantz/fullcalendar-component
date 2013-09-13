<?php

class ZoneEvent extends ZoneObject
{
	public $id;
	public $name;
	public $endTimeTS;
	public $endTime;
	public $startTimeTS;
	public $startTime;
	public $eventDateTS;
	public $eventDate;
	public $school;
	public $level;
	public $sport;
	public $activity;
	public $opponent;
	public $location;

	protected static function defaults () {
		return array();
	}

	protected static function renames () {
		return array(
			'eventID' => 'id',
			'event' => 'name'
		);
	}

	protected static function relations () {
		return array(
			'sport' => 'ZoneSport',
			'activity' => 'ZoneActivity',
			'level' => 'ZoneLevel',
			'school' => 'ZoneSchool',
			'location' => 'ZoneLocation',
			'opponent' => 'ZoneOpponent'
		);
	}

	public static function normalizeTemporal($date, $startTime, $endTime, $timeZoneName) {
		/* Event Date */
		$originalDate = NULL;
		if (preg_match(
				'#(?P<date>[a-zA-Z]+ [0-9]+ [0-9]+) (?P<midnight>12:00AM)?#', $date, $m
			) && !array_key_exists('midnight', $m)
		) {
			// Set date to 12:00AM on the 'date'.
			$originalDate = $date;
			$date = $m['date'] . " 12:00AM";
		}
		if (is_string($date) && strlen($date))
			$date = strtotime($date." ".$timeZoneName);
		else
			$date = 0;

		/* Event Start Time */
		if (is_string($startTime) && strlen($startTime)) {
			if ($date == 0 && preg_match('#(?P<date>[a-zA-Z]+ [0-9]+ [0-9]+)#', $startTime, $m)) {
				// date was null; use start-time to derive event-date.
				$date= strtotime($m['date']." 12:00AM ".$timeZoneName);
			}
			$startTime = strtotime($startTime." ".$timeZoneName);
		} else if ($originalDate !== NULL)
			// startTime was null; if and only if the date was TRUNCATED,
			// use origonal event-date for start time.
			$startTime = strtotime($originalDate." ".$timeZoneName);
		else
			$startTime = 0;

		/* Event End Time */
		if (is_string($endTime) && strlen($endTime))
			$endTime = strtotime($endTime." ".$timeZoneName);
		else
			$endTime = 0;
		return array($date, $startTime, $endTime);
	}
}
