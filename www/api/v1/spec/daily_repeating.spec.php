<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("specs for getUserEventsForCalendar", function($then){
		$then->beforeEach("with two static events", function($scene){
			$scene->eventList  = Fixture::get('events', true);
			$calendar = new Calendar();
			$calendar->calendar_id = 'cal_1';
			$calendar->creator_id  = 'frank';
			$calendar->calendar_admin = 'frank';
			$scene->calendar=$calendar;
			$scene->startPeriod=strtotime("10/10/2013");
			$scene->endPeriod= strtotime("10/31/2013");
			TestEvent::setBatch($scene->eventList['static_1'], $scene->eventList['static_2']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, $scene->startPeriod, $scene->endPeriod);
		})->
		the("events should come back", function($scene){
			expect(sizeof($scene->events))->toBe(2);
		});

		$then->beforeEach("with one finite daily repeating event", function($scene){
			TestEvent::setBatch($scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, $scene->startPeriod, $scene->endPeriod);
		})->
		the("result should contain the right number of days", function($scene){
			expect(sizeof($scene->events))->toBe(6);
		})->
		the("events should be at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("10/15/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("10/16/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("10/17/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("10/18/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("10/19/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("10/20/2013 5:00PM");
		})->
		the("events should end at the right time", function($scene){
			expect($scene->events[0]->end)->toBeTimestamp("10/15/2013 7:00PM");
			expect($scene->events[1]->end)->toBeTimestamp("10/16/2013 7:00PM");
			expect($scene->events[2]->end)->toBeTimestamp("10/17/2013 7:00PM");
			expect($scene->events[3]->end)->toBeTimestamp("10/18/2013 7:00PM");
			expect($scene->events[4]->end)->toBeTimestamp("10/19/2013 7:00PM");
			expect($scene->events[5]->end)->toBeTimestamp("10/20/2013 7:00PM");
		})->
		beforeEach("when the interval is every other day", function($scene){
			$scene->eventList['repeat_daily']['repeat_interval'] = 2;
			TestEvent::setBatch($scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, $scene->startPeriod, $scene->endPeriod);
		})->
		the("result should contain fewer events", function($scene){
			expect(sizeof($scene->events))->toBe(3);
		})->
		the("events should be at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("10/15/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("10/17/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("10/19/2013 5:00PM");
		})->
		the("events should end at the right time", function($scene){
			expect($scene->events[0]->end)->toBeTimestamp("10/15/2013 7:00PM");
			expect($scene->events[1]->end)->toBeTimestamp("10/17/2013 7:00PM");
			expect($scene->events[2]->end)->toBeTimestamp("10/19/2013 7:00PM");
		})->
		beforeEach("when the interval is every third day", function($scene){
			$scene->eventList['repeat_daily']['repeat_interval'] = 3;
			TestEvent::setBatch($scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, $scene->startPeriod, $scene->endPeriod);
		})->
		the("result should contain fewer events", function($scene){
			expect(sizeof($scene->events))->toBe(2);
		})->
		the("events should be at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("10/15/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("10/18/2013 5:00PM");
		})->
		the("events should end at the right time", function($scene){
			expect($scene->events[0]->end)->toBeTimestamp("10/15/2013 7:00PM");
			expect($scene->events[1]->end)->toBeTimestamp("10/18/2013 7:00PM");
		});
	});
?>
