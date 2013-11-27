<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'testSuite.php';

	$calendar = new Calendar();
	$calendar->calendar_id = 'cal_1';
	$calendar->creator_id  = 'frank';
	$calendar->calendar_admin = 'frank';

	$sitch = new Situation(array(
		'startPeriod'=>strtotime("10/10/2013"),
		'endPeriod'=> strtotime("10/31/2013"),
		'calendar'=>$calendar,
		'eventList'=>FixturesFor('events')));

	// Testing that static events return correctly when passed in by themselves
	When(function ($sitch){
		TestEvent::setBatch($sitch->eventList['static_1'], $sitch->eventList['static_2']);
		$sitch->events = TestEvent::getUserEventsForCalendar('frank', $sitch->calendar, $sitch->startPeriod, $sitch->endPeriod);
	}, $sitch);
	Expect(sizeof($sitch->events))->toBe(2);

	// Testing that events can be altered through the makeFrom factory
	When(function ($sitch){
		$updateWith   = array("start"=>"frank", "end"=>"paul");
		$baseEvent    = TestEvent::eventFromFixture($sitch->eventList['repeat_daily']);
		$sitch->event = TestEvent::makeFrom($baseEvent, $updateWith);
	}, $sitch);
	Expect($sitch->event)->toBeTypeOf('TestEvent');
	Expect($sitch->event->start)->toBe("frank");
	Expect($sitch->event->end)->toBe("paul");
	Expect($sitch->event)->toNotHaveProperty('repeat_interval');
	Expect($sitch->event)->toNotHaveProperty('repeat_frequency');
	Expect($sitch->event)->toNotHaveProperty('repeat_stop');
	Expect($sitch->event)->toNotHaveProperty('repeat_by_day');
	Expect($sitch->event)->toNotHaveProperty('repeat_by_month');
	Expect($sitch->event)->toNotHaveProperty('repeat_by_monthday');

	// Testing finite daily repeating event
	When(function ($sitch){
		TestEvent::setBatch($sitch->eventList['repeat_daily']);
		$sitch->events = TestEvent::getUserEventsForCalendar('frank', $sitch->calendar, $sitch->startPeriod, $sitch->endPeriod);
	}, $sitch);
	Expect(sizeof($sitch->events))->toBe(6);
	Expect(TestEvent::timeOf($sitch->events[0]->start))->toBe("10/15/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[1]->start))->toBe("10/16/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[2]->start))->toBe("10/17/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[3]->start))->toBe("10/18/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[4]->start))->toBe("10/19/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[5]->start))->toBe("10/20/2013 5:00PM");

	// Testing repeating event with different intervals
	When(function ($sitch){
		$altEvent = $sitch->eventList['repeat_daily'];
		$altEvent['repeat_interval'] = 2;
		TestEvent::setBatch($altEvent);
		$sitch->events = TestEvent::getUserEventsForCalendar('frank', $sitch->calendar, $sitch->startPeriod, $sitch->endPeriod);
	}, $sitch);
	Expect(sizeof($sitch->events))->toBe(3);
	Expect(TestEvent::timeOf($sitch->events[0]->start))->toBe("10/15/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[1]->start))->toBe("10/17/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[2]->start))->toBe("10/19/2013 5:00PM");

	// Testing repeating event with different intervals
	When(function ($sitch){
		$altEvent = $sitch->eventList['repeat_daily'];
		$altEvent['repeat_interval'] = 3;
		TestEvent::setBatch($altEvent);
		$sitch->events = TestEvent::getUserEventsForCalendar('frank', $sitch->calendar, $sitch->startPeriod, $sitch->endPeriod);
	}, $sitch);
	Expect(sizeof($sitch->events))->toBe(2);
	Expect(TestEvent::timeOf($sitch->events[0]->start))->toBe("10/15/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[1]->start))->toBe("10/18/2013 5:00PM");

	// Testing weeksSince method
	When(function($sitch){
		$sitch->date = array();
		$sitch->date[] = strtotime("10/1/2013");  // Tuesday
		$sitch->date[] = strtotime("10/3/2013");  // Thursday
		$sitch->date[] = strtotime("10/7/2013");  // Monday
		$sitch->date[] = strtotime("10/10/2013"); // Thursday
		$sitch->date[] = strtotime("10/17/2013"); // Thursday
		$sitch->date[] = strtotime("10/20/2013"); // Sunday
		$sitch->date[] = strtotime("10/27/2013"); // Sunday
	}, $sitch);
	Expect(TestEvent::findWeeksSince($sitch->date[0], $sitch->date[1]))->toBe(0);
	Expect(TestEvent::findWeeksSince($sitch->date[0], $sitch->date[2]))->toBe(1);
	Expect(TestEvent::findWeeksSince($sitch->date[1], $sitch->date[2]))->toBe(1);
	Expect(TestEvent::findWeeksSince($sitch->date[0], $sitch->date[6]))->toBe(4);
	Expect(TestEvent::findWeeksSince($sitch->date[5], $sitch->date[6]))->toBe(1);
	Expect(TestEvent::findWeeksSince($sitch->date[2], $sitch->date[4]))->toBe(1);
	Expect(TestEvent::findWeeksSince($sitch->date[2], $sitch->date[5]))->toBe(2);
	Expect(TestEvent::findWeeksSince($sitch->date[2], $sitch->date[6]))->toBe(3);
	

	// Testing weekly repeating events
	When(function($sitch){
		TestEvent::setBatch($sitch->eventList['repeat_weekly']);
		$sitch->events = TestEvent::getUserEventsForCalendar('frank', $sitch->calendar, strtotime("9/1/2013"), strtotime("12/1/2013"), $sitch->endPeriod);
	}, $sitch);
	Expect(sizeof($sitch->events))->toBe(13);
	Expect(TestEvent::timeOf($sitch->events[0]->start))->toBe("09/18/2013 5:00PM");
	Expect(TestEvent::timeOf($sitch->events[0]->start))->toBe("09/25/2013 5:00PM");



?>
