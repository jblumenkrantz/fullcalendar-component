<?php

class BRCReminderModificationQueue extends EVAWSQueue
{
	static public $DefaultQueueNameKey = 'BRCReminderModificationQueue::DefaultQueueNameKey';
	static public $AllowQueueAccessKey = 'BRCReminderModificationQueue::AllowQueueAccessKey';
	static protected $defaultQueueName = 'com_schooldatebooks_brc_BRCReminderModificationQueue';

	static public function sharedInstance () {
		static $instance = NULL;
		if ($instance === NULL) {
			$name = EVEnvironment:: get(BRCReminderModificationQueue:: $DefaultQueueNameKey);
			$name = $name? $name: BRCReminderModificationQueue:: $defaultQueueName;
			$instance = new BRCReminderModificationQueue($name);
		}
		return $instance;
	}

	protected $plugCallback;
	protected $allowQueueAccess;
	private $inRunLoop = FALSE;
	public $automaticDelete;
	public function __construct ($name) {
		try {
			parent:: __construct($name);
			$defaultAllowQueueAccess = EVEnvironment:: get(BRCReminderModificationQueue:: $AllowQueueAccessKey);
			$this->allowQueueAccess = $defaultAllowQueueAccess !== NULL? $defaultAllowQueueAccess: TRUE;
		} catch (EVAWSException $e) {
			error_log($e);
			$this->allowQueueAccess = FALSE;
		}
		$this->plugCallback = NULL;
		$this->automaticDelete = TRUE;
		//BRCDispatcher:: addEventModificationListener(array($this, 'handleEventModification'));
		//BRCDispatcher:: addTaskModificationListener(array($this, 'handleTaskModification'));
		//BRCDispatcher:: addReminderPrefModificationListener(array($this, 'handleReminderPrefModification'));
	}

	public function plug ($callable = NULL) {
		if (is_callable($callable))
			$this->plugCallback = $callable;
		$this->allowQueueAccess = TRUE;
	}

	public function unplug () {
		$this->plugCallback = NULL;
		$this->allowQueueAccess = FALSE;
	}

	public function allowQueueAccess () {
		return $this->allowQueueAccess;
	}

	private function wrap ($message, $type) {
		return "{\"type\":".json_encode($type).",\"message\":".json_encode($message)."}";
	}

	private function unwrap (&$message, &$type) {
		$jmessage = json_decode($message);
		if (
			!is_object($jmessage) ||
			!property_exists($jmessage, 'type') ||
			!property_exists($jmessage, 'message')
		)
			throw new Exception(sprintf("%s message malformed.", get_class($this)), 1);
		$message = $jmessage->message;
		$type = $jmessage->type;
	}

	public function handleEventModification (BRCEventModification $modification) {
		if ($this->allowQueueAccess)
			$this->sendMessages($this->wrap($modification->json_encode(), BRCEventModification::$Topic));
		else if ($this->plugCallback)
			call_user_func_array($this->plugCallback, array($modification));

	}

	public function handleTaskModification (BRCTaskModification $modification) {
		if ($this->allowQueueAccess)
			$this->sendMessages($this->wrap($modification->json_encode(), BRCTaskModification::$Topic));
		else if ($this->plugCallback)
			call_user_func_array($this->plugCallback, array($modification));
	}

	public function handleReminderPrefModification (BRCReminderPrefModification $modification) {
		if ($this->allowQueueAccess)
			$this->sendMessages($this->wrap($modification->json_encode(), BRCReminderPrefModification::$Topic));
		else if ($this->plugCallback)
			call_user_func_array($this->plugCallback, array($modification));
	}

	private function handleMessagesDequeue ($messages) {
		foreach ($messages as $message) {
			try {
				if (!array_key_exists('body', $message) || !array_key_exists('receipt', $message))
					throw new Exception("AWS message malformed.", 1);
				$type = NULL;
				$body = $message['body'];
				$receipt = $message['receipt'];
				$this->unwrap($body, $type);
				switch ($type) {
					case BRCEventModification:: $Topic:
						$mod = new BRCEventModificationDequeue ($body, $receipt);
						BRCDispatcher:: dispatchEventModificationDequeue($mod);
						break;
					
					case BRCTaskModification:: $Topic:
						$mod = new BRCTaskModificationDequeue ($body, $receipt);
						BRCDispatcher:: dispatchTaskModificationDequeue($mod);
						break;

					case BRCReminderPrefModification:: $Topic:
						$mod = new BRCReminderPrefModificationDequeue ($body, $receipt);
						BRCDispatcher:: dispatchReminderPrefModificationDequeue($mod);
						break;
				}
			} catch (Exception $e) {
				error_log($e);
			}
		}
		if ($this->automaticDelete)
			$this->deleteMessages($messages);
	}

	public function runLoop ($maxNumberOfMessages = 5, $visibilityTimeout = false) {
		$this->inRunLoop = TRUE;
		while ($this->inRunLoop) {
			if (!$this->allowQueueAccess)
				throw new Exception("Queue Access has been disallowed.", 1);
			$messages = $this->receiveMessages(2, $maxNumberOfMessages, $visibilityTimeout);
			if (is_array($messages) && count($messages))
				$this->handleMessagesDequeue($messages);
		}
	}

	public function exitRunLoop ($exit) {
		$this->inRunLoop = !$exit;
	}

	public function __get ($property) {
		if (property_exists($this, $property) && method_exists($this, $property))
			return call_user_func(array($this, $property));
		return parent:: __get($property);
	}
}

/** Lazy Load for AWS-SQS connection. **/
BRCDispatcher:: addEventModificationListener(function (BRCEventModification $modification) {
	static $init = FALSE;
	if ($init === FALSE && EVEnvironment:: get(BRCReminderModificationQueue:: $AllowQueueAccessKey)) {
		$rmqueue = BRCReminderModificationQueue:: sharedInstance();
		$rmqueue->handleEventModification($modification);
		$init = TRUE;
	}
});

BRCDispatcher:: addTaskModificationListener(function (BRCTaskModification $modification) {
	static $init = FALSE;
	if ($init === FALSE && EVEnvironment:: get(BRCReminderModificationQueue:: $AllowQueueAccessKey)) {
		$rmqueue = BRCReminderModificationQueue:: sharedInstance();
		$rmqueue->handleTaskModification($modification);
		$init = TRUE;
	}
});

BRCDispatcher:: addReminderPrefModificationListener(function (BRCReminderPrefModification $modification) {
	static $init = FALSE;
	if ($init === FALSE && EVEnvironment:: get(BRCReminderModificationQueue:: $AllowQueueAccessKey)) {
		$rmqueue = BRCReminderModificationQueue:: sharedInstance();
		$rmqueue->handleReminderPrefModification($modification);
		$init = TRUE;
	}
});
