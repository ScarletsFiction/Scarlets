<?php 
namespace Scarlets\Library;
use \Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Cache
---------------------------------------------------------------------------
|
| With cache you can save any data to the volatile memory or keep it
| even the process was shutdown. 
|
| It's very recommended to have msgpack on your system
| https://github.com/msgpack/msgpack-php
|
*/

class Cache{
	public static $path = '';
	public static function init(){
		$config = Scarlets\Config::load('filesystem');
		$settings = &$config['filesystem.storage'][$config['filesystem.cache_storage']];

		if($settings['driver'] === 'localfile')
			self::$path = &$settings['path'];
	}

	public static function &get($key){
		# code...
	}

	public static function set($key, $value, $minutes=0){
		# code...
	}

	public static function has($key){
		# code...
	}

	// Get and forget
	public static function &pull($key, $value){
		# code...
	}

	public static function forget($key, $value){
		# code...
	}
	
	public static function flush($key, $value){
		# code...
	}
	
	public static function extendTime($key, $minutes){
		# code...
	}
}
Cache::init();
/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
|  - Don't use JSON for serialize cache because it's slow on large data
|  - Don't use native unserialize for user input because it's exploitable
|  - If you use native serialize, make sure you save it to file and load
|	 from that file only
|
| PHP 7.0
|  - use native serialize when you serialize often and unserialize rarely
|  - use igbinary when serialize rarely and unserialize often
|  - use msgpack if you don't know which is the best
| source: https://blobfolio.com/2017/03/benchmark-php7-serialization/
|
|  Priority: msgpack -> native -> igbinary -> JSON
|
*/