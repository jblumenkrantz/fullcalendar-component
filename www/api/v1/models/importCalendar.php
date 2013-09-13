<?php

/**
* The ImportCalendar class is used to handle all of the calendar import operations
*/

class ImportCalendar extends PinwheelModelObject
{
	public $calendar_name;
	public $color;
	public $events = array();
	public $timezone;

	/**
	*	The parent construct call static hook ImportCalendar::defaults to assign
	*	initial values to contact points properties.
	*/
	static protected function defaults () {
		return array (
			'calendar_name' => NULL,
			'color' => NULL,
			'events' => array(),
			'timezone' => NULL
		);
	}

	static public function factory ($p) {
		return new ImportCalendar($p);
	}

	public function parseICS($paramUrl) {
		$icsFile = file_get_contents($paramUrl);

		$icsData = explode("BEGIN:", $icsFile);

		$calendar = array();
		$calendar["events"] = array();

		foreach($icsData as $key => $value) {
			$icsDatesMeta[$key] = explode("\n", $value);
		}

		foreach($icsDatesMeta as $key => $value) {
			foreach($value as $subKey => $subValue) {
				if ($subValue != "") {
					if ($key != 0 && $subKey == 0) {
						$icsDates[$key]["BEGIN"] = rtrim($subValue);
					} else {
						$subValueArr = explode(":", $subValue);
						if(preg_match('/\;/',$subValueArr[0])){
							$stripKey =explode(";",$subValueArr[0]);
							$subValueArr[0] = rtrim($stripKey[0]);
						}
						if(count($subValueArr) >= 2){
							$icsDates[$key][rtrim($subValueArr[0])] = rtrim($subValueArr[1]);
						}
					}
				}
			}
		}
		foreach($icsDates as $key => $value) {
			if(rtrim($value['BEGIN']) == "VCALENDAR"){
				$this->calendar_name = $value["X-WR-CALNAME"];
				$this->color = $value["X-APPLE-CALENDAR-COLOR"];
				$this->timezone = $value["X-WR-TIMEZONE"];
			}
			if(rtrim($value['BEGIN']) != "VEVENT"){
				unset($icsDates[$key]);
			}
			else{
				$event = array(
								'event_title'=>$value["SUMMARY"],
								'event_start'=>@strtotime($value["DTSTART"]),
								'event_end'=>@strtotime($value["DTEND"])
							);
				array_push($this->events, $event);
			}
		}
		ksort($calendar);

		return $calendar;
	}
}