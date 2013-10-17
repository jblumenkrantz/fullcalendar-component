<?php

/**
* User model in-use is no different than the other models, with one
* exception. The exceptions is how the 'password' property of the
* User is handled. Any User objects returend or created from the
* datastore will not contain the 'password' property. The rational
* for this behaviour is particular to the fact that the datastore
* stores the hash of the 'password' not the 'password' itself.
* Keep in mind, however, update and create will automatically create
* the hash and store the result into the datastore.
*/

class User extends PinwheelModelObject
{
	public $user_id;
	public $user_handle;
	public $email;
	public $first_name;
	public $last_name;
	public $password;
	public $timezone;
	public $active;
	public $last_modified;
	public $version;
	public $settings;

	/**
	*	The parent construct call static hook User::defaults to assign
	*	initial values to User's properties.
	*/
	static protected function defaults () {
		return array (
			'user_id' => NULL,
			'user_handle'=>NULL,
			'email' => NULL,
			'first_name' => NULL,
			'last_name' => NULL,
			'password' => NULL,
			'timezone' => 'America/New_York',
			'active' => 1,
			'last_modified' => 0,
			'version' => 0,
			'settings' => NULL
		);
	}

	/**
	*	User::factory builds User from passed properties. Consider, User::factory
	*	does NOT consult the data-storage, see User::reload for 'refreshing' a User
	*	from datastore.
	*
	*	@param $p  User property array or user object.
	*	@return User object.
	*/
	static public function factory ($p) {
		error_log(print_r($p,true));
		return new User($p);
	}


	/**
	*	User::load builds User from datastore.
	*
	*	@param $id  User id.
	*	@param $pinsqli  MySQLConnection instance used to process queue.
	*	@return User object.
	*/
	static public function load ($id, $pinsqli = NULL) {
		return(static:: loadWithQuery(
			"SELECT
				user_id, user_handle, first_name, last_name, email, timezone, active, last_modified, version
				FROM users
				WHERE user_id = '$id'
			"
		, $pinsqli));
	}

	static public function loadActive ($id, $pinsqli = NULL) {
		return(static:: loadWithQuery(
			"SELECT
				user_id, user_handle, first_name, last_name, email, timezone, active, last_modified, version
				FROM users
				WHERE user_id = '$id'
					AND active = TRUE
			"
		, $pinsqli));
	}
	
	static public function loadSettings ($id, $pinsqli = NULL) {
		return(static:: genericQuery(
			"SELECT
				default_calendar, start_of_day, end_of_day, version as DB_version
				FROM users_settings
				WHERE user_id = '$id'
			"
		, $pinsqli));
	}

	static public function loadPasswordResetSettings ($user_id, $pinsqli = NULL) {
		return(static:: genericQuery(
			"SELECT
				pw_reset_token, pw_reset_validity
				FROM users_settings
				WHERE user_id = '$user_id'
			"
		, $pinsqli));
	}
	public function updatePasswordResetSettings ($user_id, $reset_token) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$end = time()+3600; // 1 hour validity 
		$result = $pinsqli->query(
				"UPDATE users_settings
					SET pw_reset_token = '$reset_token',
						pw_reset_validity = $end
					WHERE user_id = '{$user_id}'
				"
			);
			if ($pinsqli->errno)
				throw new Exception($pinsqli->error, 1);
	}

	/* DEPRICATION NOTICE 06-03-2013 */
	/* loadWithEmail was replaced with loadWithHandle on March 6, 2013 */
	static public function loadWithEmail ($email, $pinsqli = NULL) {
		return(static:: loadWithQuery(
			"SELECT
				user_id, user_handle, first_name, last_name, email, timezone, active, last_modified, version
				FROM users
				WHERE email = '$email'
					AND active = TRUE
			"
		, $pinsqli));
	}
	/* END OF DEPRICATED SECTION */

	static public function loadWithHandle ($user_handle, $test_active = true, $pinsqli = NULL) {
		$activeQuery = ($test_active)? 'AND active = TRUE':'';

		return(static:: loadWithQuery(
			"SELECT
				user_id, user_handle, first_name, last_name, email, timezone, active, last_modified, version
				FROM users
				WHERE user_handle = '$user_handle'
				$activeQuery
			"
		, $pinsqli));
	}

	static public function getData($userId, $pinsqli=NULL){
		$dataRay = array();
		$dataRay['calendars'] = $dataRay['events'] = $dataRay['tasks'] = array();

		//$cals = Calendar::loadUserOrgCalendars($userId);
		//$subs = Calendar::loadUserSubscriptions($userId);
		$cals = (object) array_merge((array) Calendar::loadUserOrgCalendars($userId), (array) Calendar::loadUserSubscriptions($userId));

		foreach($cals as $calendar){
			$calendar->subscribed = false;
			$calendar->color = '';
			$colorResult = static:: genericQuery("SELECT
					color, view_setting
				FROM
					calendar_subs
				WHERE
					calendar_id='{$calendar->calendar_id}'
				AND
					user_id='$userId'");
			if(sizeof($colorResult) > 0){
				$calendar->color = $colorResult[0]->color;
				$calendar->subscribed = true;
				$calendar->viewing = ($colorResult[0]->view_setting)? true:false;
			}
			$publicResult = static:: genericQuery("SELECT
					calendar_id
				FROM
					public_calendars
				WHERE
					calendar_id='{$calendar->calendar_id}'
				");
			if(sizeof($publicResult) > 0){
				$calendar->public = true;
			}else{
				$calendar->public = false;
			}
			
			if(property_exists($calendar, 'adhoc_events') && !$calendar->adhoc_events){
				unset($calendar->adhoc_events);
			}
			if($calendar->subscribed && !property_exists($calendar, 'adhoc_events')){
				$events = Event::getBatch(array("events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"));
				$tasks  = Task::getBatch(array("tasks.calendar_id='{$calendar->calendar_id}'","(tasks.creator_id='$userId' OR tasks.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}'))"));

				$calendar->events = array();
				foreach($events as $event){
					array_push($calendar->events, $event->event_id);
				}

				$calendar->tasks = array();
				foreach($tasks as $task){
					array_push($calendar->tasks, $task->task_id);
				}

				$dataRay['events'] = array_merge($dataRay['events'], $events);
				$dataRay['tasks'] = array_merge($dataRay['tasks'],  $tasks);
			}
			elseif($calendar->subscribed && $calendar->adhoc_events){
				$events = Event::getBatch(array("events.calendar_id='{$calendar->calendar_id}'","(events.creator_id='$userId' OR events.creator_id=(SELECT creator_id from calendars where calendar_id = '{$calendar->calendar_id}')) AND (events.event_id in (SELECT event_id from event_subs where event_subs.user_id = '$userId' AND event_subs.calendar_id = '{$calendar->calendar_id}'))"));

				$calendar->events = array();
				foreach($events as $event){
					array_push($calendar->events, $event->event_id);
				}

				$dataRay['events'] = array_merge($dataRay['events'], $events);
			}
			else{
				$calendar->subscribed = false;
			}
			$dataRay['calendars'][$calendar->calendar_id] = $calendar; 
		}

		return($dataRay);

	}

	static public function loadWithQuery ($query, $pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query($query);
		$user = NULL;
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$user = new User($object);
		} else
			throw new Exception($pinsqli->error, 1);
		return $user;
	}

	static public function loadNewUserOptions($pinsqli=NULL) {
		$orgs = static:: genericQuery(
			"SELECT
				org_id, org_name
				FROM organizations
			"
		, $pinsqli);
		return $orgs;
	}
	static public function validateUserName($user_handle, $reverse=false, $pinsqli=NULL){
		$matches = static:: genericQuery(
			"SELECT
				user_id
				FROM users
				WHERE
					user_handle = '$user_handle'
			"
		, $pinsqli);
		if($reverse){
			if(count($matches) >= 1){
				$isValid = true;
			}
			else{
				$isValid = false;
			}
			return $isValid;
		}
		else{
			if(count($matches) >= 1){
				$existingUser = new ExistingUserException();
				echo $existingUser->json_encode();
				exit;
			}
			else{
				return true;
			}
		}
	}
	static public function validateUserEmail($email, $reverse=false, $pinsqli=NULL){
		$matches = static:: genericQuery(
			"SELECT
				user_id
				FROM users
				WHERE
					email = '$email'
			"
		, $pinsqli);
		if($reverse){
			if(count($matches) >= 1){
				$isValid = true;
			}
			else{
				$isValid = false;
			}
			return $isValid;
		}
		else{
			if(count($matches) >= 1){
				$existingUser = new ExistingUserException();
				echo $existingUser->json_encode();
				exit;
			}
			else{
				return true;
			}
		}
	}
	static public function validateResetToken($p) {
		$now = time();
		$result = static:: genericQuery("SELECT
				pw_reset_validity, user_id
			FROM
				users_settings
			WHERE
				pw_reset_token='{$p->pw_reset_token}'
			");
		if(sizeof($result) > 0){
			$result = array_shift($result);
			$valid = ($now < ($result->pw_reset_validity))? $result:false;
		}else{
			$valid = false;
		}
		return $valid;
	}
	public function updatePassword() {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE users
				SET
					password = '{$this->password}',
					version 	= version + 1
				WHERE user_id = '{$this->user_id}'
					AND version = {$this->version}
			"
		);
		if ($pinsqli->errno) {
			throw new Exception($pinsqli->error, 1);
		}	
	}
	public function deactivateResetToken($token) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE users_settings
				SET
					pw_reset_token = NULL,
					pw_reset_validity = NULL
				WHERE user_id = '{$this->user_id}'
					AND pw_reset_token = '$token'
			"
		);
		if ($pinsqli->errno) {
			throw new Exception($pinsqli->error, 1);
		}	
	}
	public function sendResetInfo() {
		$user = $this;
		$pw_reset_token = MySQLConnection:: generateUID('pw_rst');
		$this->updatePasswordResetSettings($this->user_id, $pw_reset_token);
		//$queryParameters = array('pw_rst'=>$pw_reset_token);
		//$queryString = http_build_query($queryParameters);
		$url = $_SERVER['HTTP_REFERER']."#/reset_password/".$pw_reset_token ;
		//$this->delete();
		$messageBody['html'] = "<html>
									<body lang='en' style='background-color:#fff; color: #222'>
										<div style=\"-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');\">
	                      					<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
												<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin:0 0 16px; font-size:18px; font-weight:normal'>
													Forgot your password?
												</h2>
											</div>
											<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
												<p>
													Pinwheel received a request to reset the password for your account.
												</p>
												<p>
													To reset your password, click on the link below (or copy and paste the URL into your browser):<br/>
													<a href='$url'>$url</a>
												</p>
												<p>The link will only be valid for one hour, and can only be used once.</p>
												<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
													Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
												</p>
											</div>
										</div>
									</body>
								</html>";
		$messageBody['plain'] = "Forgot your password?\r\n\r\n
								Pinwheel received a request to reset the password for your account.\r\n\r\n
								To reset your password, copy and paste the URL into your browser:\r\n
								$url\r\n\r\n
								Please do not reply to this message; it was sent from an unmonitored email address.\r\n
								This message is a service email related to your Pinwheel account.\r\n\r\n
								-Pinwheel Support
								";

		$message_id = MySQLConnection:: generateUID('password_request');
		//error_log(print_r($user_id, true));
		//error_log(print_r($recipients, true));
		//return;
		$postAuth = sha1($user->email."rainbowkitties");

		$fields = array(
								'BatchId' => $message_id,
								'Destination' => $user->email,
								'Subject' => 'Your Pinwheel Login Credentials',
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
	/**
	*	User::create stores user properties into datastore.
	*
	*	@param $p  User property array or user object.
	*	@return User object.
	*/
	static public function create ($p) {
		// Condition User Properties
		if (is_object($p))
			$p = get_object_vars($p);
		$p['user_handle'] = strtolower($p['user_handle']);
		$defaults = static:: defaults();
		if(static::loadWithHandle($p['user_handle'])){
			$userExists = new UserExistsException();
			echo $userExists->json_encode();
			exit;
		}
		$hash = NULL;
		if (array_key_exists('password', $p))
			$hash = Authorize:: hashedPassword($p['password']);
		$p = array_merge($defaults, $p);
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p = array_map(array($pinsqli, 'real_escape_string'), $p);
		$userID = MySQLConnection:: generateUID('user');
		
		// Create the user
		$resulti = $pinsqli->query(
			"INSERT INTO users (
					user_id,
					email,
					user_handle,
					first_name,
					last_name,"
					.($hash!==NULL?' password,':'').
					" timezone
				)
				Values (
					'$userID',
					'{$p['email']}',
					'{$p['user_handle']}',
					'{$p['firstName']}',
					'{$p['lastName']}',
					"
					.($hash!==NULL?" '$hash',":'').
					" '{$p['timezone']}'
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		// assign the user to the selected org
		$resulti = $pinsqli->query(
			"INSERT INTO users_orgs (
					user_id,
					org_id
				)
				Values (
					'$userID',
					'{$p['org_id']}'
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		// create user default calendar
		$dca = Calendar::create($userID,array('calendar_name'=>'Default Calendar','color'=>'blue'));
		//$dca = array_shift($dco);
		
		// make new calendar visibility turned on
		$pinsqli->query(
			"UPDATE calendar_subs
				SET	view_setting = true
				WHERE user_id = '$userID'
				AND calendar_id = '{$dca->calendar_id}'");

		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		// update users default calendar and set start/end of day settings
		$resulti = $pinsqli->query(
			"INSERT INTO users_settings (
					user_id,
					default_calendar,
					start_of_day,
					end_of_day
				)
				Values (
					'$userID',
					'{$dca->calendar_id}',
					'08:00:00',
					'17:00:00'
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
			
		$user = static:: load($userID, $pinsqli);
		return $user;
	}


	/**
	*	User::reload will 'refresh' $this User from the datastore.
	*/
	public function reload ($pinsqli = NULL) {
		$pinsqli = $pinsqli === NULL? DistributedMySQLConnection:: readInstance(): $pinsqli;
		$resulti = $pinsqli->query(
			"SELECT user_id, user_handle, first_name, last_name, email, timezone, active, last_modified, version
				From users
				WHERE user_id = '$this->user_id'
					AND version > $this->version
			"
		);
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$this->setProperties($object);
		} else
			throw new Exception($pinsqli->error, 1);
	}


	/**
	*	User::update will attempt to update properties stored in the datastore
	*	corresponding to $this User.
	*
	*	Non-optimization:
	*	Cannot batch because collision-sift query would not be atomic (scalar query result is
	*	atomic). Collision detection needs to be atomic; consider an update slipping in after
	*	UPDATE but before collision-sift query, the method would report a false positive
	*	collision.
	*/
	public function update () {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$properties = static::mysql_escape_array($this);
		$settings = static::mysql_escape_array($this->settings);

		$hash = NULL;
		if (array_key_exists('password', $properties) && $properties['password'] != ''){
			$hash = Authorize:: hashedPassword($properties['password']);
			$passwordString = "password = '$hash',";
		}
		else{
			$passwordString = '';
		}
		$resulti = $pinsqli->query(
			"UPDATE users
				SET
					email = '{$properties['email']}', timezone = '{$properties['timezone']}',
					$passwordString
					version 	= version + 1
				WHERE user_id = '{$properties['user_id']}'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->user_id, $pinsqli);
			if (!$resource)
				throw new UserDoesNotExist();
			throw new UserDataConflictException($resource, array($this));
		}
		
		// Update users settings
		$resulti2 = $pinsqli->query(
			"UPDATE users_settings
				SET
					default_calendar = '{$settings['default_calendar']}',
					start_of_day = '{$settings['start_of_day']}',
					end_of_day = '{$settings['end_of_day']}'
				WHERE user_id = '{$properties['user_id']}'
			
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		$this->reload($pinsqli);
	}

	/**
	*	User::delete will attempt to update properties stored in the datastore
	*	corresponding to $this Yser.
	*
	*	Future Optimization:
	*	Above argument (update) does not apply. If a successful delete occurs between
	*	UPDATE and collision-sift query, then the collision is dropped for the resource
	*	is in desired state (f(a,e) = f(b,e) : a != b and f(...f(f(d,e))...) = f(d,e)
	*	where a,b,d are requests and e is user and f is delete-function). As a result,
	*	User::delete could be batched.
	*/
	public function delete () {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE users
				SET
					active = FALSE,
					version = version + 1
				WHERE user_id = '$this->user_id'
					AND version = $this->version
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$resource = static:: load($this->user_id, $pinsqli);
			if (!$resource)
				throw new UserDoesNotExist();
			throw new UserDataConflictException($resource, array($this));
		}
		$this->version += 1;
		$this->active = FALSE;
	}

	/**
	*	Fetch the password hash for User ($userID).
	*
	*	@param $userID  User identification string.
	*	@return Password hash string.
	*/
	static function fetchUserPasswordHash ($userID) {
		$pinsqli = DistributedMySQLConnection:: readInstance();
		$resulti = $pinsqli->query(
			"SELECT password From users WHERE user_id = '$userID'"
		);
		$password = NULL;
		if (!$pinsqli->errno) {
			if (($object = $resulti->fetch_object()))
				$password = $object->password;
		} else
			throw new Exception($pinsqli->error, 1);
		return $password;
	}
	static function incrementVersion($userID) {
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$resulti = $pinsqli->query(
			"UPDATE users_settings
				SET
					version = version + 1
				WHERE user_id = '$userID'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			throw new UserDoesNotExist();
		}
	}
}
