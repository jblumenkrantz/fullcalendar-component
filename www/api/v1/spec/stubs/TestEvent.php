<?php
	include 'models/PinwheelModelObject.php';
	include 'models/event.php';
	class TestEvent extends Event {
		static public $batch;

		static public function getBatch($where=array(), $opts=array()){
			$ret = array();
			foreach(static::$batch as $event){
				if((IsSet($opts['repeaters']) && IsSet($event->repeat_interval)) ||
					 (IsSet($opts['addendums']) && IsSet($event->repeat_addendum)) ||
					 (!IsSet($opts['addendums']) && !IsSet($opts['repeaters']) && !IsSet($event->repeat_addendum) && !IsSet($event->repeat_interval))
					){
							$event->repeat_stop   = strtotime($event->repeat_stop);
							$event->start = strtotime($event->start);
							$event->end   = strtotime($event->end);
							$ret[] = $event;
				}
			}
			return($ret);
		}

		static public function setBatch(){
			$args = func_get_args();
			foreach($args as $i => $event){
				$event['active'] = true;
				$args[$i] = new TestEvent($event);
			}
			static:: $batch = $args;
		}

		static public function eventFromFixture($event){
			$event = new TestEvent($event);
			$event->repeat_stop   = strtotime($event->repeat_stop);
			$event->start = strtotime($event->start);
			$event->end   = strtotime($event->end);
			return $event;
		}

		static public function tellIsOnDay($day, $test){
			return static::isOnDay($day, $test);
		}

		static public function findMonthsSince($first, $second){
			return static::monthsSince($first, $second);
		}

		static public function findWeeksSince($first, $second){
			return static::weeksSince($first, $second);
		}
		static public function findDayBeginning($day){
			return static::beginningOf($day);
		}
		static public function findDayEnd($day){
			return static::endOf($day);
		}

		static public function getStatsFor($day){
			return static::statsForDate($day);
		}
	}
?>
