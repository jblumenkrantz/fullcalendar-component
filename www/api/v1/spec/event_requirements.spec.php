<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("passing values to set batch", function($then){
		$then->beforeEach("when addendums and repeaters are added", function($scene){
			$scene->eventList  = Fixture::get('events', true);
			$calendar = new Calendar();
			$calendar->calendar_id = 'cal_1';
			$calendar->creator_id  = 'frank';
			$calendar->calendar_admin = 'frank';
			$scene->calendar=$calendar;
			$scene->startPeriod=strtotime("10/10/2013");
			$scene->endPeriod= strtotime("10/31/2013");
			TestEvent::setBatch($scene->eventList['daily_addendum'], $scene->eventList['repeat_daily']);
			//$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"));

			$scene->addendums = TestEvent::getBatch(array(), array('addendums'=>1));
			$scene->repeaters = TestEvent::getBatch(array(), array('repeaters'=>1));
		})->
		the("repeaters should be returned appropriately", function($scene){
			expect($scene->repeaters[0])->toBeTypeOf('TestEvent');
			expect($scene->repeaters[0]->id)->toBe('event_3');
		})->
		the("addendums should be returned appropriately", function($scene){
			expect($scene->addendums[0])->toBeTypeOf('TestEvent');
			expect($scene->addendums[0]->id)->toBe('event_6');
		});
	});
	Scenario::when("testing ancillary methods for repeating events", function($then){
		$then->the("event model should be able to tell if a timestamp occurs within a specific day", function($scene){
			$date = strtotime("9/23/1982");
			$test = strtotime("9/23/1982 8:00AM");
			expect(TestEvent::tellIsOnDay($date, $test))->toBe(true);
		})->the("event model should be able to tell if a timestamp does not occur within a specific day", function($scene){
			$date = strtotime("9/24/1982");
			$test = strtotime("9/23/1982 8:00AM");
			expect(TestEvent::tellIsOnDay($date, $test))->toBe(false);
		});

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
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[1]))->toEqual(0);
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[2]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[1], $scene->date[2]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[0], $scene->date[6]))->toEqual(4);
			expect(TestEvent::findWeeksSince($scene->date[5], $scene->date[6]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[4]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[5]))->toEqual(2);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[6]))->toEqual(3);
		})->
		the("reverse should be true as well", function($scene){
			expect(TestEvent::findWeeksSince($scene->date[1], $scene->date[0]))->toEqual(0);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[0]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[2], $scene->date[1]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[6], $scene->date[0]))->toEqual(4);
			expect(TestEvent::findWeeksSince($scene->date[6], $scene->date[5]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[4], $scene->date[2]))->toEqual(1);
			expect(TestEvent::findWeeksSince($scene->date[5], $scene->date[2]))->toEqual(2);
			expect(TestEvent::findWeeksSince($scene->date[6], $scene->date[2]))->toEqual(3);
		});

			;

		$then->beforeEach("doing specific spot checks", function($scene){
			$scene->eventstart = strtotime("9/15/2013 5:00PM");
			$scene->weektest   = strtotime("9/25/2013 5:00PM");
		})->
		the("dates should be the right time apart", function($scene){
			expect(TestEvent::findWeeksSince($scene->eventstart, $scene->weektest))->toEqual(1);
		});

		$then->beforeEach("finding how many months have passed between two dates", function($scene){
			$scene->date = array();
			$scene->date[] = strtotime("1/1/2013");  // Tuesday
			$scene->date[] = strtotime("1/15/2013");  // Thursday
			$scene->date[] = strtotime("2/7/2013");  // Monday
			$scene->date[] = strtotime("2/20/2013"); // Thursday
			$scene->date[] = strtotime("3/1/2013"); // Thursday
			$scene->date[] = strtotime("3/25/2013"); // Sunday
			$scene->date[] = strtotime("4/27/2013"); // Sunday
		})->
		the("number should change based on the first day of a new month", function($scene){
			expect(TestEvent::findMonthsSince($scene->date[1], $scene->date[0]))->toBe(0);
			expect(TestEvent::findMonthsSince($scene->date[2], $scene->date[0]))->toBe(1);
			expect(TestEvent::findMonthsSince($scene->date[2], $scene->date[1]))->toBe(1);
			expect(TestEvent::findMonthsSince($scene->date[6], $scene->date[0]))->toBe(3);
			expect(TestEvent::findMonthsSince($scene->date[6], $scene->date[5]))->toBe(1);
			expect(TestEvent::findMonthsSince($scene->date[4], $scene->date[2]))->toBe(1);
			expect(TestEvent::findMonthsSince($scene->date[5], $scene->date[1]))->toBe(2);
			expect(TestEvent::findMonthsSince($scene->date[6], $scene->date[0]))->toBe(3);
		});

		$then->the("suite should be able to find the beginning of a day", function($scene){
			expect(TestEvent::findDayBeginning($scene->eventstart))->toBeTimestamp("9/15/2013 12:00AM");
		});
		$then->the("suite should be able to find the end of a day", function($scene){
			expect(TestEvent::findDayEnd($scene->eventstart))->toBeTimestamp("9/15/2013 11:59:59PM");
		});
	});

?>
