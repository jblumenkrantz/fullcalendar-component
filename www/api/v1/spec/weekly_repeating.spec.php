<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');
	Scenario::when("specs for getUserEventsForCalendar", function($then){
		$then->beforeEach("with one weekly repeating event", function($scene){
			$scene->eventList  = Fixture::get('events', true);
			$calendar = new Calendar();
			$calendar->calendar_id = 'cal_1';
			$calendar->creator_id  = 'frank';
			$calendar->calendar_admin = 'frank';
			$scene->calendar=$calendar;
			$scene->startPeriod=strtotime("10/10/2013");
			$scene->endPeriod= strtotime("10/31/2013");
			TestEvent::setBatch($scene->eventList['repeat_weekly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("right number of events should come back", function($scene){
			expect(sizeof($scene->events))->toBe(11);
		})->
		the("first week to be at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("09/18/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("09/25/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("10/02/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("10/09/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("10/16/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("10/23/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("10/30/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("11/06/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("11/13/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("11/20/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("11/27/2013 5:00PM");
		})->
		the("first week to end at the right time", function($scene){
			expect($scene->events[0]->end)->toBeTimestamp("09/18/2013 7:00PM");
			expect($scene->events[1]->end)->toBeTimestamp("09/25/2013 7:00PM");
			expect($scene->events[2]->end)->toBeTimestamp("10/02/2013 7:00PM");
			expect($scene->events[3]->end)->toBeTimestamp("10/09/2013 7:00PM");
			expect($scene->events[4]->end)->toBeTimestamp("10/16/2013 7:00PM");
			expect($scene->events[5]->end)->toBeTimestamp("10/23/2013 7:00PM");
			expect($scene->events[6]->end)->toBeTimestamp("10/30/2013 7:00PM");
			expect($scene->events[7]->end)->toBeTimestamp("11/06/2013 7:00PM");
			expect($scene->events[8]->end)->toBeTimestamp("11/13/2013 7:00PM");
			expect($scene->events[9]->end)->toBeTimestamp("11/20/2013 7:00PM");
			expect($scene->events[10]->end)->toBeTimestamp("11/27/2013 7:00PM");
		})->
		beforeEach("if the event is repeated twice a week", function($scene){
			$scene->eventList['repeat_weekly']['repeat_by_day'] = 'WED,THU';
			TestEvent::setBatch($scene->eventList['repeat_weekly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("event should happen twice as often", function($scene){
			expect(sizeof($scene->events))->toBe(22);
		})->
		the("event should happen at the right times", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("09/18/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("09/19/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("09/25/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("09/26/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("10/02/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("10/03/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("10/09/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("10/10/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("10/16/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("10/17/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("10/23/2013 5:00PM");
			expect($scene->events[11]->start)->toBeTimestamp("10/24/2013 5:00PM");
			expect($scene->events[12]->start)->toBeTimestamp("10/30/2013 5:00PM");
			expect($scene->events[13]->start)->toBeTimestamp("10/31/2013 5:00PM");
			expect($scene->events[14]->start)->toBeTimestamp("11/06/2013 5:00PM");
			expect($scene->events[15]->start)->toBeTimestamp("11/07/2013 5:00PM");
			expect($scene->events[16]->start)->toBeTimestamp("11/13/2013 5:00PM");
			expect($scene->events[17]->start)->toBeTimestamp("11/14/2013 5:00PM");
			expect($scene->events[18]->start)->toBeTimestamp("11/20/2013 5:00PM");
			expect($scene->events[19]->start)->toBeTimestamp("11/21/2013 5:00PM");
			expect($scene->events[20]->start)->toBeTimestamp("11/27/2013 5:00PM");
			expect($scene->events[21]->start)->toBeTimestamp("11/28/2013 5:00PM");
		})->
		beforeEach("if the event is repeated twice a week biweekly", function($scene){
			$scene->eventList['repeat_weekly']['repeat_interval'] = '2';
			TestEvent::setBatch($scene->eventList['repeat_weekly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("event should happen twice as often", function($scene){
			expect(sizeof($scene->events))->toBe(12);
		})->
		the("event should happen at the right times", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("09/18/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("09/19/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("10/02/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("10/03/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("10/16/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("10/17/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("10/30/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("10/31/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("11/13/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("11/14/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("11/27/2013 5:00PM");
			expect($scene->events[11]->start)->toBeTimestamp("11/28/2013 5:00PM");
		});

	});
?>
