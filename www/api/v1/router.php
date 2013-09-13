<?php
require_once (__DIR__.'/../../../vendor/autoload.php');
require_once (__DIR__.'/routes.php');
//require_once (__DIR__.'/../../../etc/db_connect.php');


# Regular Expression Router
$routes = routes();
$reqHeaders = getallheaders();
$httpVerb = array_key_exists('X-HTTP-Method-Override', $reqHeaders)?
	strtolower($reqHeaders['X-HTTP-Method-Override']):
	strtolower($_SERVER['REQUEST_METHOD']);
if(IsSet($_SERVER['PATH_INFO']) || IsSet($_SERVER['ORIG_PATH_INFO'])){
	$path = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:(isset($_SERVER['ORIG_PATH_INFO'])?$_SERVER['ORIG_PATH_INFO']:'/');
	$path = preg_replace("/\?.*/", "", $path);
	if($path == '/'){
		$path = '/index.html';
	}
	if(file_exists(substr($path, 1))){
		return false;
	}
}else{
	ini_set('display_errors', '0'); 
	$path = preg_replace("/api\/v1\//", '', $_SERVER['REQUEST_URI']);
	$path = preg_replace("/\?.*/", "", $path);
	if($path == '/'){
		$path = '/index.html';
	}
	if(file_exists(substr($path, 1))){
		return false;
	}
}

$reqHandlerDescription = null;
$reqParameters = null;
if (isset($routes[$path]))
	$reqHandlerDescription = $routes[$path];
else if ($routes) {
	$tokens = array(
            ':string' => '([a-zA-Z]+)',
            ':number' => '([0-9]+)',
            ':alpha'  => '([a-zA-Z0-9-_ ]+)',
            '::alpha' => '[a-zA-Z0-9-_ ]+',
			':date'	  => '([0-9]{4}[\-][0-9]{2}[\-][0-9]{2}\s[0-9]{2}[\:][0-9]{2}[\:][0-9]{2})',
			':email'  => '([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})',
			':any'    => '([^/]+)'
	);
	foreach ($routes as $pattern => $class) {
        $pattern = strtr($pattern, $tokens);
        if (preg_match('#^/?' . $pattern . '/?$#', $path, $matches)) {
            $reqHandlerDescription = $class;
            $reqParameters = $matches;
            break;
        }
    }
}

# Call Handler
if ($reqParameters)
	array_shift($reqParameters);
else
	$reqParameters = array();
if (gettype($reqHandlerDescription) === 'string' && class_exists($reqHandlerDescription) && method_exists($reqHandlerDescription, $httpVerb))
	call_user_func_array(array(new $reqHandlerDescription(), $httpVerb), $reqParameters);
else if (gettype($reqHandlerDescription) === 'array' && ($route = new Route($reqHandlerDescription)) && $route($httpVerb))
	call_user_func_array(array($route, $httpVerb), $reqParameters);
else {
	header("HTTP/1.0 404 Not Found");
	exit('HTTP/1.0 404 Not Found');
}

# Route Wrapper
class Route 
{
	static public $PreRequest=1, $PostRequest=2;
	static public $get='get', $post='post', $put='put', $delete='delete';
	private $description;
	function __construct ($description = array()) {
		$this->description = $description;
	}
	public function __invoke ($verb) {
		if (array_key_exists($verb, $this->description)) {
			preg_match('#^(?P<Controller>[^:]+)::(?P<Method>[^:]+)?#', $this->description[$verb], $descriptor);
			return array_key_exists('Controller', $descriptor) && array_key_exists('Method', $descriptor) && method_exists($descriptor['Controller'], $descriptor['Method']);
		}
		return FALSE;
	}
	public function __call ($method, $arguments) {
		foreach ($this->description as $descriptor) is_object($descriptor) && is_callable($descriptor) && $descriptor(Route::$PreRequest);
		preg_match('#^(?P<Controller>[^:]+)::(?P<Method>[^:]+)?#', $this->description[$method], $descriptor);
		call_user_func_array(array(new $descriptor['Controller'], $descriptor['Method']), $arguments);
		foreach ($this->description as $descriptor) is_object($descriptor) && is_callable($descriptor) && $descriptor(Route::$PostRequest);
	}
	public static function __callStatic ($class, $arguments) {
		if (class_exists($class) && method_exists($class, 'factory'))
			return call_user_func_array("$class::factory", $arguments);
	}
}
