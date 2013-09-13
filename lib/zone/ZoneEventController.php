<?php

class ZoneEventController
{
	static public function eventsForZoneID ($zoneID, $infimumTS = 0, $utcOffset = FALSE) {
		return new ZoneEventBufferedStream ($zoneID, $infimumTS, $utcOffset);
	}
}
