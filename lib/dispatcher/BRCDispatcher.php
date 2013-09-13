<?php

class BRCDispatcher extends Symfony\Component\EventDispatcher\EventDispatcher
{
	static public function sharedInstance () {
		static $dispatcher = NULL;
		if ($dispatcher === NULL)
			$dispatcher = new BRCDispatcher();
		return $dispatcher;
	}

	/**
	*	Event Modification
	*/
	static public function dispatchEventModification (BRCEventModification $modification) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCEventModification:: $Topic, $modification);
	}

	static public function addEventModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener (BRCEventModification:: $Topic, $listener);
	}

	static public function removeEventModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener (BRCEventModification:: $Topic, $listener);
	}


	static function dispatchEventModificationDequeue (BRCEventModificationDequeue $modification) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCEventModificationDequeue:: $Topic, $modification);
	}

	static public function addEventModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener (BRCEventModificationDequeue:: $Topic, $listener);
	}

	static public function removeEventModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener (BRCEventModificationDequeue:: $Topic, $listener);
	}


	/**
	*	Task Modification
	*/
	static public function dispatchTaskModification (BRCTaskModification $modification) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCTaskModification:: $Topic, $modification);
	}

	static public function addTaskModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener(BRCTaskModification:: $Topic, $listener);
	}

	static public function removeTaskModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener(BRCTaskModification:: $Topic, $listener);
	}

	static public function dispatchTaskModificationDequeue (BRCTaskModificationDequeue $modification) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCTaskModificationDequeue:: $Topic, $modification);
	}

	static public function addTaskModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener(BRCTaskModificationDequeue:: $Topic, $listener);
	}

	static public function removeTaskModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener(BRCTaskModificationDequeue:: $Topic, $listener);
	}


	/**
	*	Reminder Modification
	*/
	static public function dispatchReminderPrefModification(BRCReminderPrefModification $mod) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCReminderPrefModification:: $Topic, $mod);
	}

	static public function addReminderPrefModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener(BRCReminderPrefModification:: $Topic, $listener);
	}

	static public function removeReminderPrefModificationListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener(BRCReminderPrefModification:: $Topic, $listener);
	}

	static public function dispatchReminderPrefModificationDequeue (BRCReminderPrefModificationDequeue $mod) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->dispatch(BRCReminderPrefModificationDequeue:: $Topic, $mod);
	}

	static public function addReminderPrefModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->addListener(BRCReminderPrefModificationDequeue:: $Topic, $listener);
	}

	static public function removeReminderPrefModificationDequeueListener ($listener) {
		$dispatcher = BRCDispatcher:: sharedInstance();
		$dispatcher->removeListener(BRCReminderPrefModificationDequeue:: $Topic, $listener);
	}
}