<?php
if (!function_exists("getallheaders")) {
	function getallheaders() {
		$headers = "";

		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == "HTTP_") {
				$headers[str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))))] = $value;
			}
		}

		return $headers;
	}
}
# Authorize Extension
class Authorize
{
	static public $AuthType = 'PinwheelAuth';
	static public $Realm = 'Pinwheel Users';
	static public $SigningKeyKey = "Authorize::SigningKeyKey";
	static public $PasswordSaltKey = "Authorize::PasswordSaltKey";
	static public $TokenTTLKey = "Authorize::TokenTTLKey";
	static public $SkipURLKeyNameKey = "Authorize::MasterSwitchKey";
	static protected $tokenURL;
	static protected $sharedInstance = NULL;
	static protected $preRequestOccured = NULL;
	protected $userID;
	protected $password;
	protected $token;
	static function sharedInstance () {
		return static:: $sharedInstance;
	}

	static function factory ($userID = NULL, $password = NULL) {
		if (static:: $sharedInstance == NULL)
			static:: $sharedInstance = new Authorize($userID, $password);
		return static:: $sharedInstance;
	}

	public function __construct ($userID, $password) {
		$this->userID = $userID;
		$this->password = $password;
		$this->token = static:: requestToken();
		static:: $tokenURL = empty($_SERVER['HTTPS'])?
			"http://{$_SERVER['HTTP_HOST']}/auth/token":
			"https://{$_SERVER['HTTP_HOST']}/auth/token";
	}

	public function __invoke ($state) {
		switch ($state) {
			case Route::$PreRequest:
				static:: $preRequestOccured = TRUE;
				if (!static:: skipAuthentication())
					$this->authenticate();
			break;
			case Route::$PostRequest:break;
		}
	}

	public function authenticate () {
		try {
			if ($this->token) {
				$comp = static:: unpackToken($this->token);
				$this->userID = array_key_exists('userID', $comp)? $comp['userID'] : NULL;
				$this->token = static:: validateToken($this->token);
			}
			else if ($this->userID !== NULL && $this->password !== NULL)
				$this->token = static:: createTokenWithCredentials($this->userID, $this->password);
			else
				throw new NoCredentialsProvidedException(static:: $AuthType, static:: $Realm, static:: $tokenURL);
		} catch (NoCredentialsProvidedException $e) {
			header("HTTP/1.0 401 Not Authorized");
			echo $e->json_encode();
			exit;
		} catch (TokenAuthFailedException $e) {
			header("HTTP/1.0 401 Not Authorized");
			echo $e->json_encode();
			exit;
		} catch (UserPassAuthFailedException $e) {
			header("HTTP/1.0 401 Not Authorized");
			echo $e->json_encode();
			exit;
		} catch (UserDeactivatedException $e) {
			header("HTTP/1.0 401 Not Authorized");
			echo $e->json_encode();
			exit;
		}
		header("Authorization: PinwheelAuth pinwheel_token=$this->token");
	}

	public function userID () {
		return $this->userID;
	}

	public function token () {
		return $this->token;
	}

	static public function signingKey () {
		$key = EVEnvironment:: get(static:: $SigningKeyKey);
		if (!$key) $key = '';
		return $key;
	}

	static public function passwordSalt () {
		$salt = EVEnvironment:: get(static:: $PasswordSaltKey);
		if (!$salt) $salt = '';
		return $salt;
	}

	static public function tokenTTL () {
		$ttl = EVEnvironment:: get(static:: $TokenTTLKey);
		if (!$ttl) $ttl = 3600;
		return $ttl;
	}

	static public function skipURLKeyName () {
		$key = EVEnvironment:: get(static:: $SkipURLKeyNameKey);
		if (!$key) $key = FALSE;
		return $key;
	}

	static public function skipAuthentication () {
		$key = static:: skipURLKeyName();
		$skip = FALSE;
		if ($key !== FALSE && is_array($_GET))
			$skip = array_key_exists($key, $_GET);
		return !static:: $preRequestOccured || $skip;
	}

	static public function requestToken () {
		static $requestToken = FALSE;
		if ($requestToken === FALSE) {
			$reqheaders = getallheaders();
			if (array_key_exists('Authorization', $reqheaders)) {
				preg_match('#pinwheel_token[ \t]*=[ \t]*(?P<token>[a-zA-Z0-9+/=]+)#', $reqheaders['Authorization'], $matches);
				if (is_array($matches) && array_key_exists('token', $matches))
					$requestToken = $matches['token'];
			}
			if ($requestToken === FALSE)
				$requestToken = NULL;
		}
		return $requestToken;
	}



	static public function sign ($message) {
		return hash_hmac('sha256', $message, static:: signingKey());
	}

	static public function validateSignature ($message, $signature) {
		return $signature === static:: sign($message);
	}

	static public function unpackToken ($token) {
		$token = base64_decode($token);
		$components = explode(':', $token);
		if (count($components) > 2) {
			$components['userID'] = $components[0];
			$components['timestamp'] = intval($components[1]);
			$components['signature'] = $components[2];
		}
		return $components;
	}

	static public function packToken ($userID, $timestamp, $signature) {
		return base64_encode(implode(':', array($userID, $timestamp, $signature)));
	}

	static protected function validateToken ($token) {
		$comp = static:: unpackToken($token);
		$userID = array_key_exists('userID', $comp)? $comp['userID'] : '';
		$timestamp = array_key_exists('timestamp', $comp)? $comp['timestamp'] : 0;
		$signature = array_key_exists('signature', $comp)? $comp['signature'] : '';

		// Signature
		if (!static:: validateSignature($userID.$timestamp, $signature))
			throw new TokenAuthFailedException();
			
		// Timestamp
		$now = time();
		if ($timestamp < $now) {
			$user = User:: load($userID);
			if (!is_object($user) || !$user->active)
				throw new UserDeactivatedException();
			else {
				$timestamp = $now + static:: tokenTTL();
				$signature = static:: sign($userID.$timestamp);
				$token = static:: packToken($userID, $timestamp, $signature);
			}		
		}

		return $token;
	}

	static protected function createTokenWithCredentials ($userID, $password) {
		$isAuthenticated = User:: fetchUserPasswordHash($userID) === static:: hashedPassword($password);
		if (!$isAuthenticated)
			throw new UserPassAuthFailedException(static:: $AuthType, static:: $Realm, static:: $tokenURL);
		$timestamp = time() + static:: tokenTTL();
		$signature = static:: sign($userID.$timestamp);
		return static:: packToken($userID, $timestamp, $signature);
	}

	static public function hashedPassword ($password) {
		return hash_hmac('sha256', $password, static:: passwordSalt());
	}
}
