<?php
	include 'models/PinwheelModelObject.php';
	include 'models/event.php';
	class TestEvent extends Event {
		static public $batch;

		static public function getBatch($where=array(), $opts=array()){
			$ret = array();
			foreach(static::$batch as $event){
				if((IsSet($opts['repeaters']) && IsSet($event->repeat_interval) && $event->repeat_interval > 0) ||
						(IsSet($opts['addendums']) && IsSet($event->repeat_id)) ||
						(!IsSet($opts['addendums']) && !IsSet($opts['repeaters']) && !IsSet($event->repeat_id) && (!IsSet($event->repeat_interval) || $event->repeat_interval==0))){
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

		static public function timeOf($time){
			return strftime("%m/%d/%Y%l:%M%p", $time);
		}

		static public function findWeeksSince($first, $second){
			return static::weeksSince($first, $second);
		}
	}
?>
