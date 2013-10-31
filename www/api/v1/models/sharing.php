<?php

/**
* The Sharing class is used to handle all of the sharing tasks
* including sending of the messages for requests
*/

class Sharing extends PinwheelModelObject
{
	public $element_id;
	public $element_name;
	public $element_type;
	public $recipients;
	public $valid_users = array();
	public $share_type;
	public $share_tokens = array();

	/**
	*	The parent construct call static hook Sharing::defaults to assign
	*	initial values to Sharing properties.
	*/
	static protected function defaults () {
		return array(
			'element_id' => NULL,
			'element_name' => NULL,
			'element_type' => NULL,
			'recipients' => NULL,
			'valid_users' => array(),
			'share_type' => NULL,
			'share_tokens' => array()
		);
	}

	static public function factory ($p) {
		if (is_object($p))
			$p = array(get_object_vars($p));
		else if (array_reduce(array_keys($p),function(&$r,$k){return $r+!is_int($k);}))
			$p = array($p); # Associative
		$props = array();
		foreach ($p as $ep)
			array_push($props, new Sharing($ep));
		return $props;
	}
	
	static function getContactPoints($users_batch){
		$contactPoints = array();
		foreach ($users_batch as $userId) {
			$points = static:: genericQuery(
			"SELECT 
    			address
				FROM contact_points 
				WHERE user_id = '$userId'
				AND point_type = 'email'
				AND activated_on IS NOT NULL 
				AND emergency_only = false
			"
			);
			$userPoints = array();
			foreach ($points as $point) {
				array_push($userPoints, $point->address);
			}
			$contactPoints[$userId] = $userPoints;
			//array_push($contactPoints, $userPoints);
		}
		return $contactPoints;
	}

	static public function getUserIDs($p,$pinsqli=NULL){
		$match = static:: genericQuery(
			"SELECT
				user_id
				FROM users
				WHERE
					user_handle = '$p'
			"
		, $pinsqli);
		if(count($match) >= 1){
			return array_shift($match)->user_id;
		}
		return false;
	}

	public function convertRecipients() {
		$email = array();
		$users = array();
		foreach (explode(",", $this->recipients) as $recipient) {
			if (preg_match('/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/',$recipient)) { 
				array_push($email,$recipient);
			}
			else{
				if($userID = static::getUserIDs($recipient)){
					array_push($users,$userID);
				}
			}
		}
		$this->valid_users = $users;
		$contactPoints = static:: getContactPoints($users);
		//error_log(print_r($contactPoints,true));
/*		foreach ($contactPoints as $userPoints) {
			// REMOVE EMAIL ADDRESSES THAT ARE IN A USERS CONTACT POINT LIST
			$email = array_diff($email, $userPoints);
		}*/
		
		$this->recipients = array_merge(array('users'=>$contactPoints),array('email'=>array_unique($email)));
	}

	public function createToken($user_id=NULL) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$user_id = ($user_id == NULL)? 'NULL':"'$user_id'";
		$send_time = time();

		$shareToken = MySQLConnection:: generateUID('token');
		$result = $pinsqli->query(
				"INSERT INTO share_requests (
						share_token,
						token_active,
						element_id,
						user_id,
						share_sent
					)
					Values (
						'$shareToken',
						true,
						'{$this->element_id}',
						$user_id,
						FROM_UNIXTIME('$send_time')
					)
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);

		return $shareToken;
	}

	public function generateMessageBody($shareToken) {

		$authUserID = Authorize:: sharedInstance()->userID();
		$user = User:: load($authUserID);
		$user->first_name = ucfirst($user->first_name);
		$user->last_name = ucfirst($user->last_name);

		$queryParameters = array('token'=>$shareToken);
		$queryString = http_build_query($queryParameters);
		$url = $_SERVER['HTTP_REFERER']."?".$queryString;
		$messageBody = array();
		$typeSyntax = ($this->element_type == 'event')? 'n':'';
		error_log(print_r($this,true));
		if($this->share_type == 'subscription'){
			$messageBody['html'] = "<html>
										<body lang='en' style='background-color:#fff; color: #222'>
											<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
												<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
													<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
													$user->first_name $user->last_name shared <strong>$this->element_name</strong> with you.
												</h2>
												</div>
												<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
												<p>
													You have been given access to a$typeSyntax $this->element_type that is owned by $user->first_name $user->last_name.  To accept this request, follow the outlined instructions.
												</p>
												<p>
													<ul>
														<li>Click on the subscription link below, or copy and paste it in your browser</li>
														<li>Log in to Pinwheel, if not already logged in.</li>
														<li>Click the \"Accept\" button on the sharing panel that is revealed</li>
														<li>Thats it.</li>
													</ul>
												</p>
												<p>
													Subscription Link: <a href='$url' >$url</a>
												</p>
												<p>
													Any changes you make to this $this->element_type will not be visible to anyone else, and they will not be reflected on the $this->element_type of the user that sent this request.
												</p>
												<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
													Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
												</p>
											</div>
										</div>
										</body>
									</html>";
			$messageBody['plain'] = $user->first_name." ".$user->last_name." has shared ".$this->element_name." with you.
									You have been given access to a$typeSyntax $this->element_type that is owned by $user->first_name $user->last_name. To accept this request, follow the instructions below.

									Copy and paste the subscription link below into your browser
									Log in to Pinwheel, if not already logged in.
									Click the \"Accept\" button on the sharing panel that is revealed
									Thats it.
									Subscription Link: $url

									Any changes you make to this $this->element_type will not be visible to anyone else, and they will not be reflected on the $this->element_type of the user that sent this request.

									Please do not reply to this message; it was sent from an unmonitored email address. This message is a service email related to your Pinwheel account.";
		}
		else {
			switch ($this->element_type) {
				case 'event':
					$raw = array_shift(Event:: load($this->element_id));
					$raw->start = ($raw->allDay)? date('F j, Y', $raw->start):date('F j, Y, g:i a', $raw->start);
					$raw->end = ($raw->allDay)? date('F j, Y', $raw->end):date('F j, Y, g:i a', $raw->end);
					$messageBody['html'] = "<html>
											<body lang='en' style='background-color:#fff; color: #222'>
												<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
													<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
														<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
															$user->first_name $user->last_name has shared an event with you.
														</h2>
													</div>
													<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
														<table border='1' cellpadding='5px' width='100%'>
															<tr>
																<th>Event Title</th>
																<th>Description</th>
																<th>Start Time</th>
																<th>End Time</th>
															</tr>
															<tr>
																<td>{$raw->title}</td>
																<td>{$raw->event_description}</td>
																<td>{$raw->start}</td>
																<td>{$raw->end}</td>
															</tr>
														</table>
														<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
															Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
														</p>
													</div>
												</div>
											</body>
										</html>";

					$messageBody['plain'] = $user->first_name." ".$user->last_name." has shared an event with you\r\n \r\n
											Event Title: {$raw->title}\r\n
											Description: {$raw->event_description}\r\n
											Start Time: {$raw->start}\r\n
											End Time: {$raw->end}\r\n
											";
					break;

				case 'task':
					$raw = array_shift(Task:: load($this->element_id));
					$raw->start = ($raw->start == 0)? 'None':date('F j, Y, g:i a', $raw->start);
					$messageBody['html'] = "<html>
											<body lang='en' style='background-color:#fff; color: #222'>
												<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
													<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
														<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
															$user->first_name $user->last_name has shared a task with you.
														</h2>
													</div>
													<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
														<table border='1' cellpadding='5px' width='100%'>
															<tr>
																<th>Task Name</th>
																<th>Due Time</th>
																<th>Progress</th>
																<th>Notes</th>
															</tr>
															<tr>
																<td>{$raw->title}</td>
																<td>{$raw->start}</td>
																<td>{$raw->progress}</td>
																<td>{$raw->task_notes}</td>
															</tr>
														</table>
														<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
															Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
														</p>
													</div>
												</div>
											</body>
										</html>";
					$messageBody['plain'] = $user->first_name." ".$user->last_name." has shared a task with you \r\n \r\n
											Task Name: {$raw->title}\r\n
											Due Time: {$raw->start}\r\n
											Progress: {$raw->progress}\r\n
											Notes: {$raw->task_notes}\r\n
											";
					break;

				case 'calendar':
					$firstDOM = strtotime(date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y'))));
					$lastDOM = strtotime(date('Y-m-t', mktime(0, 0, 0, date('m'), 1, date('Y'))));

					$calendar = array_shift(Calendar:: load($this->element_id));
					$events = Event::allBetween($firstDOM,$lastDOM,$this->element_id);
					$tasks  = Task::getBatch(array("tasks.calendar_id='{$this->element_id}'"));

					$eventRows = array('html'=>null,'plain'=>null);
					foreach ($events as $event) {
						$event->start = ($event->allDay)? date('F j, Y', $event->start):date('F j, Y, g:i a', $event->start);
						$event->end = ($event->allDay)? date('F j, Y', $event->end):date('F j, Y, g:i a', $event->end);
						$eventRows['html'] .= "<tr>
											<td>{$event->title}</td>
											<td>{$event->event_description}</td>
											<td>{$event->start}</td>
											<td>{$event->end}</td>
										</tr>";
						$eventRows['plain'] .= "\r\n
											Event Title: {$event->title}\r\n
											Description: {$event->event_description}\r\n
											Start Time: {$event->start}\r\n
											End Time: {$event->end}\r\n
											";
					}

					$taskRows = array('html'=>null,'plain'=>null);
					foreach ($tasks as $task) {
						$task->start = ($task->start == 0)? 'None':date('F j, Y, g:i a', $task->start);
						$taskRows['html'] .= "<tr>
											<td>{$task->title}</td>
											<td>{$task->start}</td>
											<td>{$task->progress}</td>
											<td>{$task->task_notes}</td>
									</tr>";
						$taskRows['plain'] .= "\r\n
												Task Name: {$task->title}
												Due Time: {$task->start}
												Progress: {$task->progress}
												Notes:{$task->task_notes}
												";
					}

					$messageBody['html'] = "<html>
											<body lang='en' style='background-color:#fff; color: #222'>
												<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
													<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
														<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
															$user->first_name $user->last_name has shared $this->element_name with you.
														</h2>
													</div>
													<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
														<p>
															You have been given access to a calendar that is owned by $user->first_name $user->last_name.
														</p>
														<h2>Events</h2>
														<table border='1' cellpadding='5px'>
															<tr>
																<th>Event Title</th>
																<th>Description</th>
																<th>Start Time</th>
																<th>End Time</th>
															</tr>".
															$eventRows['html']
														."</table>
														<h2>Tasks</h2>
														<table border='1' cellpadding='5px'>
															<tr>
																<th>Task Name</th>
																<th>Due Time</th>
																<th>Progress</th>
																<th>Notes</th>
															</tr>".
															$taskRows['html']
														."</table>
														<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
															Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
														</p>
													</div>
												</div>
											</body>
										</html>";
					$messageBody['plain'] = $user->first_name." ".$user->last_name." has shared their calendar ".$this->element_name." with you.\r\n
											Events\r\n
												".
												$eventRows['plain']
											."
											Tasks\r\n
												".
												$taskRows['plain']
											;											
					
					break;

				default:
					exit;
					break;
			}
		}
		return $messageBody;
	}

	public function compileShareRequest(){
		foreach ($this->recipients['email'] as $recipient) {
			static::sendShareRequest(array($recipient));
		}
		foreach ($this->recipients['users'] as $userId => $contactPoints) {
			static::sendShareRequest($contactPoints,$userId);
		}
	}

	public function sendShareRequest($recipients,$user_id=NULL) {

		$recipientString = implode(",", $recipients);

		$shareToken = static::createToken($user_id);
		$messageBody = static::generateMessageBody($shareToken);
		$share_id = MySQLConnection:: generateUID('share_request');
		//error_log(print_r($user_id, true));
		//error_log(print_r($recipients, true));
		//return;
		$postAuth = sha1($recipients[0]."rainbowkitties");

		$fields = array(
								'BatchId' => $share_id,
								'Destination' => $recipientString,
								'Subject' => 'Pinwheel Share Request',
								'PlainBody' => $messageBody['plain'],
								'HtmlBody' => $messageBody['html'],
								'Auth' => $postAuth
						);

		$data = "";
		foreach( $fields as $key => $value ) $data .= "$key=" . urlencode( $value ) . "&";

		// run transaction
		$ch = curl_init("https://messenger-brc.sdicgdev.com"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, 1); // set to 0 to eliminate header info from response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim( $data, "& " )); // use HTTP POST to send form data

		$result = curl_exec($ch);
	}

	static function invitationDetails($p){
		$pinsqli = DistributedMySQLConnection:: readInstance();
		$result = $pinsqli->query(
				"SELECT element_id
						FROM share_requests
						WHERE user_id = '{$p->auth_user_id}'
						AND share_token = '{$p->token}'
				"
			);
		if (!$pinsqli->errno) {
			$element_id = $result->fetch_object()->element_id;
		} else
			throw new Exception($pinsqli->error, 1);
		$elementType = explode('_', $element_id)[0];
		$title = ($elementType == 'event')? 'title':'calendar_name';
		$result = $pinsqli->query(
				"SELECT {$title} AS title
					FROM {$elementType}s
					WHERE {$elementType}_id = '$element_id'
				"
			);
		if (!$pinsqli->errno) {
			$object = $result->fetch_object();
		} else
			throw new Exception($pinsqli->error, 1);
		return $object;
	}
	static function validteToken($props, $user_id, $pinsqli=NULL){
		$payload = false;
		$match = static:: genericQuery(
			"SELECT
				element_id
				FROM share_requests
				WHERE
					share_token = '{$props->token}'
				AND
					(user_id = '$user_id' OR user_id IS NULL)
			"
		, $pinsqli);
		if(count($match) >= 1){
			$payload = array_shift($match)->element_id;
		}
		return $payload;
	}
	static function alreadySubscribedEvent($props, $pinsqli=NULL){
		$payload = false;
		$match = static:: genericQuery(
			"SELECT
				id
				FROM event_subs
				WHERE
					id = '{$props->id}'
				AND
					user_id = '{$props->user_id}'
			"
		, $pinsqli);
		if(count($match) >= 1){
			$payload = true;
		}
		return $payload;
	}
	static function alreadySubscribedCalendar($props, $pinsqli=NULL){
		$payload = false;
		$match = static:: genericQuery(
			"SELECT
				calendar_id
				FROM calendar_subs
				WHERE
					calendar_id = '{$props->calendar_id}'
				AND
					user_id = '{$props->user_id}'
			"
		, $pinsqli);
		if(count($match) >= 1){
			$payload = true;
		}
		return $payload;
	}
	static function isAdhocSubscription($props, $pinsqli=NULL){

		$match = static:: genericQuery(
			"SELECT
				adhoc_events
				FROM calendar_subs
				WHERE
					calendar_id = '{$props->calendar_id}'
				AND
					user_id = '{$props->user_id}'
			"
		, $pinsqli);
		if(count($match) >= 1){
			$payload = (object) array('exists'=>true, 'adhoc'=>array_shift($match)->adhoc_events);
		}
		else{
			$payload = (object) array('exists'=>false, 'adhoc'=>false);
		}
		return $payload;
	}
	static function promoteSubscription($props){
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$result = $pinsqli->query(
				"UPDATE
				calendar_subs
				SET adhoc_events = false
				WHERE
					calendar_id = '{$props->calendar_id}'
				AND
					user_id = '{$props->user_id}'
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
		
		return true;
	}
	static function updateShareRequest($props, $pinsqli=NULL){
		$originalData = array_shift(static:: genericQuery(
			"SELECT
				user_id
				FROM share_requests
				WHERE
					share_token = '{$props->token}'
			"
		, $pinsqli));
		$pinsqli = DistributedMySQLConnection:: writeInstance();		
		//error_log(print_r($originalData,true));
		$result = $pinsqli->query(
				"UPDATE share_requests
					SET activation_attempts = activation_attempts + 1,
					user_id = (
								CASE
									WHEN
										(share_requests.user_id IS NULL)
									THEN
										'{$props->auth_user_id}'
									ELSE
										'{$originalData->user_id}'
								END
							)
					WHERE share_token = '{$props->token}'
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);

		return;
	}
}
