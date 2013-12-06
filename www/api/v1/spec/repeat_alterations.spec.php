<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("passing values to set batch", function($then){
		$then->beforeEach("when a blackout addendum is added to a repeater", function($scene){
			$scene->eventList  = Fixture::get('events', true);
			$calendar = new Calendar();
			$calendar->calendar_id = 'cal_1';
			$calendar->creator_id  = 'frank';
			$calendar->calendar_admin = 'frank';
			$scene->calendar=$calendar;
			$scene->startPeriod=strtotime("10/10/2013");
			$scene->endPeriod= strtotime("10/31/2013");
			TestEvent::setBatch($scene->eventList['daily_blackout'], $scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("repeated events without blackout dates should be added appropriately", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp('10/15/2013 5:00PM');
		})->
		the("blackout should cause an event to not be added", function($scene){
			expect($scene->events)->sizeToBe(5);
		})->
		the("correct event should be missing", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp('10/15/2013 5:00PM');
			expect($scene->events[1]->start)->toBeTimestamp('10/16/2013 5:00PM');
			expect($scene->events[2]->start)->toBeTimestamp('10/18/2013 5:00PM');
			expect($scene->events[3]->start)->toBeTimestamp('10/19/2013 5:00PM');
			expect($scene->events[4]->start)->toBeTimestamp('10/20/2013 5:00PM');
		});

		$then->beforeEach("when a non-blackout addendum is added to a repeater", function($scene){

			$scene->eventList['repeat_daily']['repeat_interval'] = 2;	
			TestEvent::setBatch($scene->eventList['daily_addendum'], $scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the('right number of events should come back', function($scene){
			expect($scene->events)->sizeToBe(4);
		})->
		the('events should be at the right time', function($scene){
			expect($scene->events[0]->start)->toBeTimestamp('10/15/2013 5:00PM');
			expect($scene->events[1]->start)->toBeTimestamp('10/17/2013 5:00PM');
			expect($scene->events[2]->start)->toBeTimestamp('10/18/2013 7:00PM');
			expect($scene->events[3]->start)->toBeTimestamp('10/19/2013 5:00PM');
		});

		$then->beforeEach("when the time of an event is changed but kept on the same day", function($scene){
			$scene->eventList['repeat_daily']['repeat_interval'] = 1;	
			TestEvent::setBatch($scene->eventList['daily_blackout'], $scene->eventList['daily_addendum'], $scene->eventList['repeat_daily']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("event should be moved appropriately", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp('10/15/2013 5:00PM');
			expect($scene->events[1]->start)->toBeTimestamp('10/16/2013 5:00PM');
			expect($scene->events[2]->start)->toBeTimestamp('10/18/2013 7:00PM');
			expect($scene->events[3]->start)->toBeTimestamp('10/19/2013 5:00PM');
			expect($scene->events[4]->start)->toBeTimestamp('10/20/2013 5:00PM');
		});
	});


?>

