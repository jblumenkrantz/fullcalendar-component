<?php
	include 'mocks/DistributedMySQLConnection.php';
	include 'mocks/Authorize.php';
	include 'mocks/Calendar.php';

	include 'stubs/TestEvent.php';
	include 'bdd4php/testSuite/scenario.php';

	Fixture::at('spec/fixtures');

	Scenario::when("finding the number of times a day of the week has occurred in a month", function($then){
		$then->beforeEach("from the beginning of the month", function($scene){
			$scene->dateStats = TestEvent::getStatsFor(strtotime("10/16/2013"));
		})->
		the("result should know what day of the week the date was", function($scene){
			expect($scene->dateStats['dayName'])->toBe('WED');
		})->
		the("result should know the number of times that day happens in a month", function($scene){
			expect($scene->dateStats['repeated'])->toEqual(5);
		})->
		the("result should know the number of times that day has happened", function($scene){
			expect($scene->dateStats['currently'])->toEqual(3);
		});

		$then->beforeEach("when the date in question is not included in the first week of the month", function($scene){
			$scene->dateStats = TestEvent::getStatsFor(strtotime("10/14/2013"));
		})->
		the("result should know what day of the week the date was", function($scene){
			expect($scene->dateStats['dayName'])->toBe('MON');
		})->
		the("result should know the number of times that day happens in a month", function($scene){
			expect($scene->dateStats['repeated'])->toEqual(4);
		})->
		the("result should know the number of times that day has happened", function($scene){
			expect($scene->dateStats['currently'])->toEqual(2);
		});

		
	});
?>
