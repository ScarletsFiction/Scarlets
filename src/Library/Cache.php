<?php 
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Cache\FileSystem;
use \Scarlets\Library\Cache\Redis;

/*
---------------------------------------------------------------------------
| Scarlets Cache
---------------------------------------------------------------------------
|
| With cache you can save any data to the volatile memory or keep it
| even the process was shutdown. 
|
|
*/

class Cache{
	public static $connectedDB = [];
	public static $default = false;
	public static $credentials = false;

	public static function &connect($credential=false){
		// Use default credential if not specified
		if($credential === false)
			$credential = self::$default;

		// Return the connected database
		if(isset(self::$connectedDB[$credential]))
			return self::$connectedDB[$credential];

		// Get reference of requested database credential
		$ref = &self::$credentials[$credential];
		$copy = false;

		// Check if it's referenced to other credential
		if(isset(self::$credentials[$credential]['connection'])){
			$requestedCredential = $credential;
			$credential = $ref['connection'];

			// Try to obtain copy after connected
			$copy = true;
		}

		// Connect if not connected
		if(!isset(self::$connectedDB[$credential])){
			$options = &self::$credentials[$credential];
			$driver = &$options['driver'];

			if($driver === 'filesystem')
				self::$connectedDB[$credential] = new FileSystem($options);
			elseif($driver === 'redis')
				self::$connectedDB[$credential] = new Redis($options);
			// Else ...
		}
		$db = &self::$connectedDB[$credential];

		// Obtain the clone
		if($copy){
			$copy = clone $db;
			$copy->change($ref);
			self::$connectedDB[$requestedCredential] = &$copy;
			return $copy;
		}

		return $db;
	}

	public static function init(){
		$config = Config::load('cache');
		self::$default = &$config['cache.cache_storage'];
		self::$credentials = &$config['cache.storage'];
	}
}
Cache::init();