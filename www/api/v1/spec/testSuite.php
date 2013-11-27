<?php

class Colors{
	// http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
	private $foreground_colors = array();
	private $background_colors = array();

	public function __construct() {
		$this->foreground_colors['black'] = '0;30';
		$this->foreground_colors['dark_gray'] = '1;30';
		$this->foreground_colors['blue'] = '0;34';
		$this->foreground_colors['light_blue'] = '1;34';
		$this->foreground_colors['green'] = '0;32';
		$this->foreground_colors['light_green'] = '1;32';
		$this->foreground_colors['cyan'] = '0;36';
		$this->foreground_colors['light_cyan'] = '1;36';
		$this->foreground_colors['red'] = '0;31';
		$this->foreground_colors['light_red'] = '1;31';
		$this->foreground_colors['purple'] = '0;35';
		$this->foreground_colors['light_purple'] = '1;35';
		$this->foreground_colors['brown'] = '0;33';
		$this->foreground_colors['yellow'] = '1;33';
		$this->foreground_colors['light_gray'] = '0;37';
		$this->foreground_colors['white'] = '1;37';
		$this->background_colors['black'] = '40';
		$this->background_colors['red'] = '41';
		$this->background_colors['green'] = '42';
		$this->background_colors['yellow'] = '43';
		$this->background_colors['blue'] = '44';
		$this->background_colors['magenta'] = '45';
		$this->background_colors['cyan'] = '46';
		$this->background_colors['light_gray'] = '47';
	}
		 
		 // Returns colored string
	public function getColoredString($string, $foreground_color = null, $background_color = null) {
		$colored_string = "";

		// Check if given foreground color found
		if (isset($this->foreground_colors[$foreground_color])) {
		$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
		}
		// Check if given background color found
		if (isset($this->background_colors[$background_color])) {
		$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
		}

		// Add string and end coloring
		$colored_string .=  $string . "\033[0m";

		return $colored_string;
	}
} 

function xWhen(){}
function xExpect(){}
function When(){
	$argRay = func_get_args();
	$func = array_shift($argRay);
	call_user_func_array($func, $argRay);
}

function Expect($val){
	return new Expectation($val);
}

function FixturesFor($name){
	return json_decode(file_get_contents("spec/fixtures/$name.json"), true);
}

class Expectation{
	static public $testValue;
	static public $colors;
	function __construct($test){
		static:: $testValue = $test;
		static:: $colors = new Colors();
	}

	function success(){
		print(static::$colors->getColoredString('.', 'green'));
	}

	function fail($msg){
		print static::$colors->getColoredString("\n\n$msg\n", 'red');
		debug_print_backtrace();
	}

	function toNotHaveProperty($arg){
		if( IsSet(static::$testValue->{$arg}) ){
			static::fail("Expected object to not have property $arg, but it had value ".static::$testValue->{$arg});
		}else{
			static::success();
		}
	}

	function toBeTypeOf($arg){
		if( get_class(static::$testValue) != $arg ){
			static::fail("Expected ".static::$testValue." to be of class $arg, but was ".get_class(static::$testValue));
		}else{
			static::success();
		}
	}
	function toBe($arg){
		if( static::$testValue != $arg ){
			static::fail("Expected ".static::$testValue." to be $arg");
		}else{
			static::success();
		}
	}

	function toBeGreaterThan($arg){
		if( static::$testValue <= $arg ){
			static::fail("Expected ".static::$testValue." to be greater than ".$arg);
		}else{
			static::success();
		}
	}
}

class Situation {
	function __construct($ray){
		foreach($ray as $name => $val){
			$this->{$name} = $val;
		}
	}
}

?>
