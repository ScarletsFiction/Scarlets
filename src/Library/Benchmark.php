<?php
namespace Scarlets\Library;

class Benchmark{
	public static $iteration = 1000;

	// Return elapsed time in seconds
	public static function this($func){
		$start = microtime(true);
		for ($i=0, $n=self::$iteration; $i < $n; $i++) { 
			$func();
		}
		return microtime(true) - $start;
	}
}