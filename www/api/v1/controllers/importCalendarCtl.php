<?php

class ImportCalendarCtl
{
	function importICS () {
		$body = json_decode(Request:: body());
		//$authUserID = ($authUserID = Authorize:: sharedInstance()->userID());
		$import = new ImportCalendar();
		// TODO: upload file
		$import->parseICS("calendarImport/ics_import_test.ics");
		// TODO: add new calendar
		// TODO: add events to calendar
		// TODO: subscribe user to new calendar
		echo json_encode($import);
		// TODO: Remove uploaded file
	}
}