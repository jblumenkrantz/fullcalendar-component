<?php

/*
	Wrapper for AWS SQS Queue API object.
*/
class EVAWSQueue extends EVAWSAuth
{
	/**
	*	@return singleton AmazonSQS v1 Class Instance.
	*/
	static public function sqsInstance () {
		static $sqsInstance = NULL;
		if ($sqsInstance === NULL) {
			$sqsInstance = new AmazonSQS(array(
				'key' => EVAWSQueue:: getKey(),
				'secret'=> EVAWSQueue:: getSecret()
			));
		}
		return $sqsInstance;
	}



	/**
	*	@return array of queue names.
	*/
	static public function names () {
		$queueURLs = EVAWSQueue:: sqsInstance()->get_queue_list();
		$queueNames = array();
		foreach ($queueURLs as $url) {
			array_push($queueNames, basename($url));
		}
		return count($queueNames) == 0? NULL: $queueNames;
	}



	/**
	*	@param $name  name of the queue to be bound.
	*	@return  singleton EVAWSQueue instance bound to the queue $name.
	*/
	static public function queue ($name) {
		static $queueInstances = array();
		if (!array_key_exists($name, $queueInstances)) {
			$queue = new EVAWSQueue($name);
			$queueInstances [$name] = $queue;
		}
		return $queueInstances [$name];
	}



	private $_name, $_url;
	public function __construct ($name) {
		$url = EVAWSQueue:: sqsInstance()->create_queue($name);
		if ($url->isOK()) {
			$url = $url->body->CreateQueueResult->QueueUrl->to_array();
			$url = $url [0];
		} else
			throw new EVAWSException ($url->body->Error);
		$this->_name = $name;
		$this->_url = $url;
	}

	private function _sendMessage ($message) {
		$messageId = EVAWSQueue:: sqsInstance()->send_message($this->_url, $message);
		if ($messageId->isOK()) {
			$messageId = $messageId->body->SendMessageResult->MessageId->to_array();
			$messageId = $messageId [0];
		}
		else
			throw new EVAWSException ($messageId->body->Error);
		return $messageId;
	}



	/**
	*	@param $message  message to send. The type can 'string' or 'array'.
	*	@return  array of message id(s).
	*/
	public function sendMessages ($messages) {
		if (gettype($messages) == 'string')
			return array ($this->_sendMessage($messages));
		$messagesPackage = array();
		$messageIndex = 0;
		foreach ($messages as $message)
			array_push($messagesPackage, array('Id' => $messageIndex++, 'MessageBody' => $message));
		$messageIds = EVAWSQueue:: sqsInstance()->send_message_batch($this->_url, $messagesPackage);

		if ($messageIds->isOK()) {
			$messageIds = $messageIds->body->SendMessageBatchResult->to_array();
			$messageIds = $messageIds ['SendMessageBatchResultEntry'];
			$batchResult = $messageIds;
			$messageIds = array();
			foreach ($batchResult as $messageID) {
				array_push($messageIds, $messageID['MessageId']);
			}
		} else
			throw new EVAWSException ($messageIds->body->Error);
		return $messageIds;
	}



	/**
	*	@param $waitForSeconds  integer defining the blocking timeout.
	*	@param $maxNumberOfMessages  interger defining the maximum number of returned messages.
	*	@param $visiblityTimeout  integer defining how long a message will be hidden in the queue after dequeue.
	*	@return array of 'array-message's.
	*/
	public function receiveMessages ($waitForSeconds = false, $maxNumberOfMessages = false, $visibilityTimeout = false) {
		$parameters = array();
		if  ($waitForSeconds !== false)
			$parameters ['WaitTimeSeconds'] = $waitForSeconds;
		if ($maxNumberOfMessages !== false)
			$parameters ['MaxNumberOfMessages'] = $maxNumberOfMessages;
		if ($visibilityTimeout !== false)
			$parameters ['VisibilityTimeout'] = $visibilityTimeout;
		$messages = EVAWSQueue:: sqsInstance()->receive_message($this->_url, $parameters);
		if ($messages->isOK()) {
			$messages = $messages->body->ReceiveMessageResult;
			if (isset($messages->Message)) {
				$messageBatch = $messages->to_array();
				$messageBatch = $messageBatch ['Message'];
				$messages = array();
				if (array_key_exists('Body', $messageBatch)) {
					// Singular
					array_push($messages, array (
							'body' => $messageBatch['Body'],
							'md5' => $messageBatch['MD5OfBody'],
							'receipt' => $messageBatch['ReceiptHandle'],
							'id' => $messageBatch['MessageId']
						));
				} else {
					foreach ($messageBatch as $message) {
						array_push($messages, array (
							'body' => $message['Body'],
							'md5' => $message['MD5OfBody'],
							'receipt' => $message['ReceiptHandle'],
							'id' => $message['MessageId']
						));
					}
				}
			}
			else
				$messages = NULL;
		} else
			throw new EVAWSException ($messages->body->Error);
		return $messages;
	}



	/**
	*	@param $receipt  message receipt(s) used to identify message(s). Parameter can be of type 'string',
	*		'array-messages', or 'array' of 'array-messages'.
	*	@param $timeout  value(s) defining the visiblity of a message(s),
	*		and is of type number value, or an array with count($receipt) == count ($timeout),
	*		if and only if the $receipt is an 'array' of 'messages-array'.
	*/
	public function changeMessagesVisiblity ($receipt, $timeout)
	{
		$isArray = false;
		$timeOutIsArray = gettype($timeout) === 'array';
		$response = NULL;
		if (gettype($receipt) == 'string')
			$response = EVAWSQueue:: sqsInstance()->change_message_visibility($this->_url, $receipt, $timeout);
		else if (gettype($receipt) == 'array' && ($isArray = true) &&  array_key_exists('receipt', $receipt))
			$response = EVAWSQueue:: sqsInstance()->change_message_visibility($this->_url, $receipt ['receipt'], $timeout);
		else if ($isArray) {
			$receiptPackage = array();
			$receiptIndex = 0;
			foreach ($receipt as $receiptHandle) {
				if (array_key_exists('receipt', $receiptHandle)) {
					if ($timeOutIsArray) {
						array_push($receiptPackage, array('Id' => $receiptIndex, 'ReceiptHandle' => $receiptHandle['receipt'], 'VisibilityTimeout' => $timeout[$receiptIndex]));
						$receiptIndex++;
					}
					else
						array_push($receiptPackage, array('Id' => $receiptIndex++, 'ReceiptHandle' => $receiptHandle['receipt'], 'VisibilityTimeout' => $timeout));
				}
			}
			if (count($receiptPackage))
				$response = EVAWSQueue:: sqsInstance()->change_message_visibility_batch($this->_url, $receiptPackage);
		}
		if ($response && !$response->isOK())
			throw new EVAWSException ($response->body->Error);
	}



	/**
	*	@param $receipt message receipt(s) used to identify message(s). Parameter can be of type 'string',
	*		'array-messages', or 'array' of 'array-messages'.
	*/
	public function deleteMessages ($receipt) {
		$response = NULL;
		$isArray = false;
		if (gettype($receipt) == 'string')
			$response = EVAWSQueue:: sqsInstance()->delete_message($this->_url, $receipt);
		else if (gettype($receipt) == 'array' && ($isArray = true) && array_key_exists('receipt', $receipt))
			$response = EVAWSQueue:: sqsInstance()->delete_message($this->_url, $receipt ['receipt']);
		else if ($isArray) {
			$receiptPackage = array();
			$receiptIndex = 0;
			foreach ($receipt as $receiptHandle) {
				if (array_key_exists('receipt', $receiptHandle))
					array_push($receiptPackage, array('Id' => $receiptIndex++, 'ReceiptHandle' => $receiptHandle['receipt']));
			}
			if (count($receiptPackage))
				$response = EVAWSQueue:: sqsInstance()->delete_message_batch($this->_url, $receiptPackage);
		}
		if ($response && !$response->isOK())
			throw new EVAWSException ($response->body->Error);
	}




	/**
	*	Deletes the queue this instance is bound.
	*/
	public function delete () {
		$response = EVAWSQueue:: sqsInstance()->delete_queue($this->_url);
		if ($response && !$response->isOK())
			throw new EVAWSException ($response->body->Error);
	}



	/**
	*	PHP override method used to lookup undefined instance properties.
	*
	*	The current supported properties are one-to-one mappings of the SQS Queue properties:
	*		ApproximateNumberOfMessages, ApproximateNumberOfMessagesNotVisible, VisibilityTimeout,
	*		CreatedTimestamp, LastModifiedTimestamp, Policy, MaximumMessageSize, MessageRetentionPeriod,
	*		QueueArn, ApproximateNumberOfMessagesDelayed, DelaySeconds, ReceiveMessageWaitTimeSeconds,
	*		Name, URL
	*/
	public function __get ($name) {
		$attribute = NULL;
		if ($name === "Name")
			$attribute = $this->_name;
		else if ($name === "URL")
			$attribute = $this->_url;
		else {
			$attribute = EVAWSQueue:: sqsInstance()->get_queue_attributes($this->_url, array('AttributeName' => array($name)));
			if ($attribute->isOk()) {
				$attribute = $attribute->body->GetQueueAttributesResult->Attribute->to_array();
				$attribute = $attribute ['Value'];
			} else
				throw new EVAWSException ($attribute->body->Error);
		}
		return $attribute;
	}

	/**
	*	@return string description of the the bound queue.
	*/
	public function description () {
		$description  = "Queue Name: $this->_name".PHP_EOL;
		$description .= "Queue URL: $this->_url".PHP_EOL;
		$description .= "Queue Attributes:".PHP_EOL;
		$response = EVAWSQueue:: sqsInstance()->get_queue_attributes($this->_url, array(
			'AttributeName' => array(
				'ApproximateNumberOfMessages',
				'ApproximateNumberOfMessagesNotVisible',
				'VisibilityTimeout',
				'CreatedTimestamp',
				'LastModifiedTimestamp',
				'Policy',
				'MaximumMessageSize',
				'MessageRetentionPeriod',
				'QueueArn',
				'ApproximateNumberOfMessagesDelayed',
				'DelaySeconds',
				'ReceiveMessageWaitTimeSeconds'
			)
		));
		if ($response->isOK()) {
			$queueAttributes = $response->body;
			$attrArray = $queueAttributes->GetQueueAttributesResult->to_array();
			$attrArray = $attrArray['Attribute'];
			foreach ($attrArray as $array) {
				$name = $array['Name'];
				$value = $array['Value'];
				$description .= sprintf("  %-38s %s%s", $name, $value, PHP_EOL);
			}
		}
		return $description;
	}
}
