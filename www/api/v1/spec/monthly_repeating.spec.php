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
			$scene->startPeriod=strtotime("1/1/2013");
			$scene->endPeriod=strtotime("12/1/2013");
			TestEvent::setBatch($scene->eventList['repeat_monthly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar,  $scene->startPeriod, $scene->endPeriod);
		})->
		the("event should happen the right number of times", function($scene){
			expect(sizeof($scene->events))->toBe(11);
		})->
		the("should happen at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("01/12/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("02/12/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("03/12/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("04/12/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("05/12/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("06/12/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("07/12/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("08/12/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("09/12/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("10/12/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("11/12/2013 5:00PM");
		})->
		the("should end at the right time", function($scene){
			expect($scene->events[0]->end)->toBeTimestamp("01/12/2013 7:00PM");
			expect($scene->events[1]->end)->toBeTimestamp("02/12/2013 7:00PM");
			expect($scene->events[2]->end)->toBeTimestamp("03/12/2013 7:00PM");
			expect($scene->events[3]->end)->toBeTimestamp("04/12/2013 7:00PM");
			expect($scene->events[4]->end)->toBeTimestamp("05/12/2013 7:00PM");
			expect($scene->events[5]->end)->toBeTimestamp("06/12/2013 7:00PM");
			expect($scene->events[6]->end)->toBeTimestamp("07/12/2013 7:00PM");
			expect($scene->events[7]->end)->toBeTimestamp("08/12/2013 7:00PM");
			expect($scene->events[8]->end)->toBeTimestamp("09/12/2013 7:00PM");
			expect($scene->events[9]->end)->toBeTimestamp("10/12/2013 7:00PM");
			expect($scene->events[10]->end)->toBeTimestamp("11/12/2013 7:00PM");
		});

		$then->beforeEach("that repeats on multiple days", function($scene){
			$scene->eventList['repeat_monthly']['repeat_by_monthday'] = '12,13';
			TestEvent::setBatch($scene->eventList['repeat_monthly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("1/1/2013"), strtotime("12/1/2013"));
		})->
		the("event should happen the right number of times", function($scene){
			expect(sizeof($scene->events))->toBe(22);
		})->
		the("should happen at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("01/12/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("01/13/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("02/12/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("02/13/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("03/12/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("03/13/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("04/12/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("04/13/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("05/12/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("05/13/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("06/12/2013 5:00PM");
			expect($scene->events[11]->start)->toBeTimestamp("06/13/2013 5:00PM");
			expect($scene->events[12]->start)->toBeTimestamp("07/12/2013 5:00PM");
			expect($scene->events[13]->start)->toBeTimestamp("07/13/2013 5:00PM");
			expect($scene->events[14]->start)->toBeTimestamp("08/12/2013 5:00PM");
			expect($scene->events[15]->start)->toBeTimestamp("08/13/2013 5:00PM");
			expect($scene->events[16]->start)->toBeTimestamp("09/12/2013 5:00PM");
			expect($scene->events[17]->start)->toBeTimestamp("09/13/2013 5:00PM");
			expect($scene->events[18]->start)->toBeTimestamp("10/12/2013 5:00PM");
			expect($scene->events[19]->start)->toBeTimestamp("10/13/2013 5:00PM");
			expect($scene->events[20]->start)->toBeTimestamp("11/12/2013 5:00PM");
			expect($scene->events[21]->start)->toBeTimestamp("11/13/2013 5:00PM");
		});

		$then->beforeEach("every other month", function($scene){
			$scene->eventList['repeat_monthly']['repeat_by_monthday'] = '12,13';
			$scene->eventList['repeat_monthly']['repeat_interval'] = '2';
			TestEvent::setBatch($scene->eventList['repeat_monthly']);
			$scene->events = TestEvent::getUserEventsForCalendar('frank', $scene->calendar, strtotime("1/1/2013"), strtotime("12/1/2013"));
		})->
		the("event should happen the right number of times", function($scene){
			expect(sizeof($scene->events))->toBe(12);
		})->
		the("should happen at the right time", function($scene){
			expect($scene->events[0]->start)->toBeTimestamp("01/12/2013 5:00PM");
			expect($scene->events[1]->start)->toBeTimestamp("01/13/2013 5:00PM");
			expect($scene->events[2]->start)->toBeTimestamp("03/12/2013 5:00PM");
			expect($scene->events[3]->start)->toBeTimestamp("03/13/2013 5:00PM");
			expect($scene->events[4]->start)->toBeTimestamp("05/12/2013 5:00PM");
			expect($scene->events[5]->start)->toBeTimestamp("05/13/2013 5:00PM");
			expect($scene->events[6]->start)->toBeTimestamp("07/12/2013 5:00PM");
			expect($scene->events[7]->start)->toBeTimestamp("07/13/2013 5:00PM");
			expect($scene->events[8]->start)->toBeTimestamp("09/12/2013 5:00PM");
			expect($scene->events[9]->start)->toBeTimestamp("09/13/2013 5:00PM");
			expect($scene->events[10]->start)->toBeTimestamp("11/12/2013 5:00PM");
			expect($scene->events[11]->start)->toBeTimestamp("11/13/2013 5:00PM");
		});
	});
?>
