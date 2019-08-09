<?php 
namespace Scarlets\Internal;
use \Scarlets;
use \Scarlets\Console;
use \Scarlets\Extend\Strings;

/*
---------------------------------------------------------------------------
| Scarlets Unit Test
---------------------------------------------------------------------------
|
| Currently no description
|
*/

class UnitTest{
	private static $currentStatus = true;
	public static function it($desc, $func){
		self::desc($desc);
		self::$currentStatus = true;

		$memory = memory_get_usage();
		$time = microtime(true);

		try{
			$func('\Scarlets\Internal\UnitTest');
		} catch(UnitTestFailed $e){}

		$time = round((microtime(true) - $time) * 1000);
		$mem = Strings::formatBytes(memory_get_peak_usage()-$memory);

		if(self::$currentStatus === true)
			$status = Console::chalk("Success", 'green');
		elseif(self::$currentStatus === false)
			$status = Console::chalk("Failed", 'red');
		else
			$status = Console::chalk(self::$currentStatus, 'yellow');

		echo "\n    $status    ~$time ms    Mem:$mem";
	}

	public static function describe($text){
		echo "\n".Console::chalk($text, 'cyan');
	}

	private static function finish($type){
		if($type === false){
			self::$currentStatus = false;
			throw new UnitTestFailed();
		}
		elseif($type === true)
			self::$currentStatus = true;
		else self::$currentStatus = $type;
	}

	private static function desc(&$text){
		echo "\n • $text";
	}

	public static function equal($what, $with){
		if($what === $with)
			self::finish(true);
		elseif($what == $with)
			self::finish("Partially");
		else self::finish(false);
	}

	public static function true($what){
		if($what === true)
			self::finish(true);
		elseif($what == true)
			self::finish("Partially");
		else self::finish(false);
	}

	public static function false($what){
		if($what === false)
			self::finish(true);
		elseif($what == false)
			self::finish("Partially");
		else self::finish(false);
	}
}

// For throwing an failed event on the middle of execution
class UnitTestFailed extends \Exception{}