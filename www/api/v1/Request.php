<?php
# Request Extension
class Request
{
	protected static $sharedInstance = NULL;
	protected $contentType;
	protected $rawPostBody;
	static function sharedInstance () {
		return static:: $sharedInstance;
	}
	static function factory ($contentType) {
		if (static:: $sharedInstance == NULL)
			static:: $sharedInstance = new Request($contentType);
		return static:: $sharedInstance;
	}
	public function __construct ($contentType = 'application/json') {
		$this->contentType = $contentType;
	}
	public function __invoke ($state) {
		switch ($state) {
			case Route::$PreRequest:
				$this->rawPostBody = file_get_contents('php://input');
				break;
		}
	}

	static public function body () {
		return static:: sharedInstance()->rawPostBody;
	}

	static public function path () {
		return isset($_SERVER['PATH_INFO'])?
			$_SERVER['PATH_INFO']:(isset($_SERVER['ORIG_PATH_INFO'])?
				$_SERVER['ORIG_PATH_INFO']:'/'
			);
	}
	
	static public function pathValue ($key) {
		$value = NULL;
		if (array_key_exists($key, $_GET))
			$value = $_GET[$key];
		return $value;
	}
}