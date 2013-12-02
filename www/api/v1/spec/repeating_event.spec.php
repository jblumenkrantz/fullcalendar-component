<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("testing ancillary methods for repeating events", function($then){
		$then->beforeEach("when altering a repeating event", function($scene){
			$eventList    = Fixture::get('events', true);
			$updateWith   = array("start"=>"frank", "end"=>"paul");
			$baseEvent    = TestEvent::eventFromFixture($eventList['repeat_daily']);
			$scene->event = TestEvent::makeFrom($baseEvent, $updateWith);
		})->
		the("event should be of the proper type", function($scene){
			expect($scene->event)->toBeTypeOf('TestEvent');
		})->
		the("attributes should be updated", function($scene){
			expect($scene->event->start)->toBe('frank');
			expect($scene->event->end)->toBe('paul');
		})->
		the("repeat related attributes should be removed", function($scene){
			expect($scene->event)->not()->toHaveProperty('repeat_interval');
			expect($scene->event)->not()->toHaveProperty('repeat_frequency');
			expect($scene->event)->not()->toHaveProperty('repeat_stop');
			expect($scene->event)->not()->toHaveProperty('repeat_by_day');
			expect($scene->event)->not()->toHaveProperty('repeat_by_month');
			expect($scene->event)->not()->toHaveProperty('repeat_by_monthday');
		});

		$then->beforeEach("finding how many weeks have passed between two dates", function($scene){
			$scene->date = array();
			$scene->date[] = strtotime("10/1/2013");  // Tuesday
			$scene->date[] = strtotime("10/3/2013");  // Thursday
			$scene->date[] = strtotime("10/7/2013");  // Monday
			$scene->date[] = strtotime("10/10/2013"); // Thursday
			$scene->date[] = strtotime("10/17/2013"); // Thursday
			$scene->date[] = strtotime("10/20/2013"); // Sunday
			$scene->date[] = strtotime("10/27/2013"); // Sunday
		})->
		the("number should change based on the sunday/monday transitions that have passed", function($scene){
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[1]))->toBe(0);
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[2]))->toBe(1);
			expect(TestEvent::findWeeksSince($scene->date[1], $scene->date[2]))->toBe(1);
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[6]))->toBe(4);
			expect(TestEvent::findWeeksSince($scene->date[5], $scene->date[6]))->toBe(1);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[4]))->toBe(1);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[5]))->toBe(2);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[6]))->toBe(3);
		});
	});

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
			expect(TestEvent::timeOf($scene->events[0]->start))->toBe("10/15/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[1]->start))->toBe("10/16/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[2]->start))->toBe("10/17/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[3]->start))->toBe("10/18/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[4]->start))->toBe("10/19/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[5]->start))->toBe("10/20/2013 5:00PM");
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
			expect(TestEvent::timeOf($scene->events[0]->start))->toBe("10/15/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[1]->start))->toBe("10/17/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[2]->start))->toBe("10/19/2013 5:00PM");
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
			expect(TestEvent::timeOf($scene->events[0]->start))->toBe("10/15/2013 5:00PM");
			expect(TestEvent::timeOf($scene->events[1]->start))->toBe("10/18/2013 5:00PM");
		});

		$then->beforeEach("with one weekly repeating event", function($scene){
			TestEvent::setBatch($scene->eventList['repeat_weekly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));
		})->
		the("right number of events should come back", function($scene){
			expect(sizeof($scene->events))->toBe(13);
		})->
		the("first week to be at the right time", function($scene){
			expect(TestEvent::timeOf($scene->events[0]->start))->toBe("9/18/2013 5:00PM");
		});
	});
?>
