<?php

/**
* The contact class is used to handle all of the users contact point
* operations
*/

class Contact extends PinwheelModelObject
{
	public $user_id;
	public $point_id;
	public $point_type;
	public $country_code;
	public $address;
	public $emergency_only;
	public $version;

	/**
	*	The parent construct call static hook Contact::defaults to assign
	*	initial values to contact points properties.
	*/
	static protected function defaults () {
		return array (
			'user_id' => NULL,
			'point_id' => NULL,
			'point_type' => NULL,
			'country_code' => '1',
			'address' => NULL,
			'emergency_only' => FALSE,
			'version' => 0
		);
	}

	static public function factory ($p) {
		return new Contact($p);
	}

	static public function loadContactPoint ($id, $pinsqli = NULL) {
		$contactPoint = static:: genericQuery(
			"SELECT user_id, point_id, point_name, point_type, country_code, address, emergency_only, activated_on, version
				From contact_points
				WHERE point_id = '$id'
			"
		, $pinsqli);

		foreach($contactPoint as $key => $point) {
			$contactPoint[$key]->emergency_only = ($contactPoint[$key]->emergency_only == '1') ? true:false;
			$contactPoint[$key]->activated = ($contactPoint[$key]->activated_on != null) ? true:false;
			unset($contactPoint[$key]->activated_on);
		}
		return $contactPoint;
	}

	static function getContactPoints($user_id, $pinsqli=NULL) {
		$contactPoints = static:: genericQuery(
			"SELECT user_id, point_id, point_name, point_type, country_code, activated_on, address, emergency_only, version
				From contact_points
				WHERE user_id = '$user_id'
			"
		, $pinsqli);
		$contactPointObject = null;
		foreach ($contactPoints as $key => $value) {
			$value->emergency_only = ($value->emergency_only == '1') ? true:false;
			$value->activated = ($value->activated_on != null) ? true:false;
			unset($value->activated_on);
			//$contactPointObject[$value->point_id] = $value;
		}
		return $contactPoints;
	}

	public function generateMessage($activationCode, $p) {
		//error_log(print_r($p,true));
		$url = $_SERVER['HTTP_REFERER']."#/activate_contact_point/".$activationCode ;
		if($p['point_type'] === "mobile"){
			$messageBody['html'] = "{$p['point_name']} Activation Code: $activationCode";
			$messageBody['plain'] = $messageBody['html'];
		}
		else{
			$messageBody['html'] = "<html>
									<body lang='en' style='background-color:#fff; color: #222'>
										<div style='-moz-box-shadow: 0px 5px 16px #999;-webkit-box-shadow:0px 5px 16px #999;box-shadow: 0px 5px 16px #999;-ms-filter: 'progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999')';filter:progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=90, Color='#999999');'>
											<div style='background:#AAA; margin-bottom:0px; padding:10px;'>
												<h2 style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:18px; margin:0px; font-weight:normal'>
												Contact Point Activation
											</h2>
											</div>
											<div style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif; font-size:13px; padding: 14px; background:#DDD; position:relative'>
											<p>
												Pinwheel received a request to activate a point of contact for you.
											</p>
											<p>
												In order for Pinwheel to use your newly created contact point it must first be activated by clicking the link below or pasting it into your browser.
											</p>
											<p>
												Activating your contact point is necesary to ensure that you recieve updates and reminders to your calendars and subscribed calendars.
											</p>
											<p>
												Emergency Alerts will also be sent to your activated contact points.  To be sure you recieve important Emergency Alerts from your school or organization, be sure to activate this contact point.
											</p>
												<strong>{$p['point_name']}</strong><br/>
												<strong>Activation Link: <a href='$url'>$url</a></strong>
											</p>
											<p style='font-family: Helvetica Neue, Arial, Helvetica, sans-serif;margin-top:5px;font-size:10px;color:#888888;'>
												Please do not reply to this message; it was sent from an unmonitored email address.  This message is a service email related to your Pinwheel account.
											</p>
										</div>
									</div>
									</body>
								</html>";
		$messageBody['plain'] = "Contact Point Activation
								Pinwheel received a request to activate a point of contact for you.

								In order for Pinwheel to use your newly created contact point it must
								first be activated by clicking the link below or pasting it into your browser.

								{$p['point_name']}
								Activation Link: $url

								Please do not reply to this message; it was sent from an unmonitored email address. This message is a service email related to your Pinwheel account.
								";		
		}
		

		return $messageBody;
	}
	static function createContactPoint($user_id, $p) {
		error_log('create contact point');
		if (is_object($p))
			$p = get_object_vars($p);

		$hash = NULL;

		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p = array_map(array($pinsqli, 'real_escape_string'), $p);
		$point_id = MySQLConnection:: generateUID('contact_point');
		$activationCode = MySQLConnection:: generateUID('activate_point');
		$countryCode = ($p['point_type'] == 'mobile')? "1":"NULL";
		$resulti = $pinsqli->query(
			"INSERT INTO contact_points (
					user_id,
					point_id,
					point_name,
					point_type,
					address,
					emergency_only,
					activation_code,
					country_code
				)
				Values (
					'$user_id',
					'$point_id',
					'{$p['point_name']}',
					'{$p['point_type']}',
					'{$p['address']}',
					'{$p['emergency_only']}',
					'$activationCode',
					$countryCode
				)
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		$message = static::generateMessage($activationCode, $p);
		$msg = array(
			'Type' => 'email',
			'Destination' => $p['address'],
			'Subject' => 'Pinwheel Contact Point Activation',
			'PlainBody' => $message['plain'],
			'HtmlBody' => $message['html']
		);

		if($p['point_type'] === "mobile"){
			$msg['Type'] = 'sms';
			$carrier_id = static:: validateMobileAddress($p['address']);
			if($carrier_id){
				$msg['Destination'] = "1".$p['address']."-".$carrier_id;
				$resulti = $pinsqli->query(
					"UPDATE contact_points
						SET
							carrier_id = $carrier_id
						WHERE point_id = '$point_id'
							AND user_id = '$user_id'
					"
				);
				if ($pinsqli->errno)
					throw new Exception($pinsqli->error, 1);
			}else{
				$mobileAddressError = new MobileAddressException();
				echo $mobileAddressError->json_encode();
				exit;
			}
		}

		static:: sendActivationCode($msg);
		$contactPoint = static:: loadContactPoint($point_id, $pinsqli);
		return $contactPoint;
	}

	static function updateContactPoint($user_id, $p){
		if (is_object($p))
			$p = get_object_vars($p);

		$hash = NULL;
		$currentData = array_shift(static:: loadContactPoint($p['point_id']));
		// If user changed the contact points address or type, the activation process needs to be run again
		if($currentData->address != $p['address'] || $currentData->point_type != $p['point_type']){
			$activationCode = MySQLConnection::generateActivationCode();
			$countryCode = ($p['point_type'] == 'mobile')? "country_code = 1":"country_code = NULL";
			$activation = "activated_on = NULL, $countryCode , activation_code = '$activationCode', address = '{$p['address']}', point_type ='{$p['point_type']}',";
		}
		else{
			$activation = NULL;
		}
		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p = static::object_to_array($p);
		// Query
		$resulti = $pinsqli->query(
			"UPDATE contact_points
				SET
					point_name = '{$p['point_name']}',
					emergency_only = '{$p['emergency_only']}',
					".$activation."
					version 	= version + 1
				WHERE point_id = '{$p['point_id']}'
					AND version = '{$p['version']}'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);

		//$contactPoint = array_shift(static:: loadContactPoint($p['point_id'], $pinsqli));
		if($activation != NULL){
			$message = static::generateMessage($activationCode, $p);
			$msg = array(
				'Type' => 'email',
				'Destination' => $p['address'],
				'Subject' => 'Pinwheel Contact Point Activation',
				'PlainBody' => $message['plain'],
				'HtmlBody' => $message['html']
			);
			//error_log(print_r($p,true));
			if($p['point_type'] === "mobile"){
				$msg['Type'] = 'sms';
				$carrier_id = static:: validateMobileAddress($p['address']);
				if($carrier_id){
					$msg['Destination'] = "1".$p['address']."-".$carrier_id;
					$resulti = $pinsqli->query(
						"UPDATE contact_points
							SET
								carrier_id = $carrier_id
							WHERE point_id = '{$p['point_id']}'
								AND user_id = '{$p['user_id']}'
						"
					);
					if ($pinsqli->errno)
						throw new Exception($pinsqli->error, 1);
				}else{
					$mobileAddressError = new MobileAddressException();
					echo $mobileAddressError->json_encode();
					exit;
				}
			}
			static:: sendActivationCode($msg);
		}
		$contactPoint = array_shift(static:: loadContactPoint($p['point_id'], $pinsqli));
		return $contactPoint;
	}

	static function deleteContactPoint($user_id, $p){
		if (is_object($p))
			$p = get_object_vars($p);

		$hash = NULL;

		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p = static::object_to_array($p);
		// Query
		$resulti = $pinsqli->query(
			"DELETE 
				FROM contact_points
				WHERE point_id = '{$p['point_id']}'
				AND version = '{$p['version']}'
				AND user_id = '$user_id'
			"
		);
		if ($pinsqli->errno)
			throw new Exception($pinsqli->error, 1);
		
		return $p;
	}
	static function activateContactPoint($user_id, $p){
		if (is_object($p))
			$p = get_object_vars($p);

		$hash = NULL;

		$pinsqli = DistributedMySQLConnection:: writeInstance();
		$p = array_map(array($pinsqli, 'real_escape_string'), $p);
		// Query
		$resulti = $pinsqli->query(
			"UPDATE contact_points
				SET
					activated_on = NOW(),
					version 	= version + 1
				WHERE user_id = '$user_id'
				AND activation_code = '{$p['activation_token']}'
			"

		);
		if ($pinsqli->errno)
			throw new ActivationException($pinsqli->error, 1);
		if ($pinsqli->affected_rows == 0) {
			$activationError = new ActivationException();
			echo $activationError->json_encode();
			exit;
		}
		$contactPoint = static:: loadContactPoint($p['point_id'], $pinsqli);
		return $contactPoint;
	}

	public function sendActivationCode($message) {
		$recipient = $message['Destination'];

		$contact_point_activation_id = MySQLConnection:: generateUID('contact_point_activation');

		$postAuth = sha1($recipient."rainbowkitties");

		$fields = array(
								'BatchId' => $contact_point_activation_id,
								'Destination' => $recipient,
								'Subject' => $message['Subject'],
								'PlainBody' => $message['PlainBody'],
								'HtmlBody' => $message['HtmlBody'],
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

	static function validateMobileAddress($address){
		$smsValues = array
			(
			"PhoneNumber"		=> "1".$address,
			"Username"			=> "sdieventlink",
			"Password"			=> "datebooks",
			"RequestType"		=> "Carrier"
			);
			
		$fields = http_build_query($smsValues);

		// run transaction
		$ch = curl_init("https://nspreview.celltrust.net/preview/PreviewConnector?");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);	// use HTTP POST to send form data
		$resp = curl_exec($ch);							//execute post and get results
		curl_close ($ch);
		
		$xml = simplexml_load_string($resp);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

		if(array_key_exists('Error', $array)) {
			return false;
		}else{
			$carrier_id = $array['PreviewInfo']['CarrierInfo']['CarrierId'];
			return $carrier_id;
		}
	}
}