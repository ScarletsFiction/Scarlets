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
	public static function it($desc, $func){
		self::desc($desc);
		$memory = memory_get_usage();
		$start = microtime(true);
		$func('\Scarlets\Internal\UnitTest');
		echo "    ~".round((microtime(true) - $start) * 1000).' ms';
		echo "    Mem:".Strings::formatBytes(memory_get_peak_usage()-$memory);
	}

	private static function finish($type){
		if($type === false)
			$ret = Console::chalk("Failed", 'red');
		elseif($type === true)
			$ret = Console::chalk("Success", 'green');
		else $ret = Console::chalk($type, 'yellow');

		echo "\n    $ret";
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