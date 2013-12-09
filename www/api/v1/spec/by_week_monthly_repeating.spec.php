<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("specs for getUserEventsForCalendar", function($then){
		$then->beforeEach("finding the second monday of a month", function($scene){
			$scene->eventList  = Fixture::get('events', true);
			$calendar = new Calendar();
			$calendar->calendar_id = 'cal_1';
			$calendar->creator_id  = 'frank';
			$calendar->calendar_admin = 'frank';
			$scene->calendar=$calendar;
			$scene->startPeriod=strtotime("1/1/2013");
			$scene->endPeriod=strtotime("12/1/2013");

			unset($scene->eventList['repeat_monthly']['repeat_by_monthday']);
			$scene->eventList['repeat_monthly']['start'] = '1/9/2013 5:00PM';
			$scene->eventList['repeat_monthly']['end'] = '1/9/2013 7:00PM';
			$scene->eventList['repeat_monthly']['repeat_by_day'] = 'WED';
			$scene->eventList['repeat_monthly']['repeat_position'] = '2';
			TestEvent::setBatch($scene->eventList['repeat_monthly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar,  $scene->startPeriod, $scene->endPeriod);
		})->
		the("event should happen the right number of times", function($scene){
			expect(sizeof($scene->events))->toBe(11);
		})->
		the("should happen at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("01/9/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("02/13/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("03/13/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("04/10/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("05/8/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("06/12/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("07/10/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("08/14/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("09/11/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("10/9/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("11/13/2013 5:00PM");
		});

	});
?>
