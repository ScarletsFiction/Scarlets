<?php 
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Cache\LocalFile;
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

	/**
	 * Connect to cache
	 *
	 * @param string $credential CredentialID that configured on the configuration
	 * @return \Scarlets\Library\Cache\LocalFile
	 */
	public static function &connect($credential = false){
		// Use default credential if not specified
		if($credential === false)
			$credential = self::$default;

		// Return the connected database
		if(isset(self::$connectedDB[$credential]))
			return self::$connectedDB[$credential];

		// Connect if not connected
		if(!isset(self::$connectedDB[$credential])){
			$options = &self::$credentials[$credential];
			$driver = &$options['driver'];

			if($driver === 'localfile')
				self::$connectedDB[$credential] = new LocalFile($options);
			elseif($driver === 'redis')
				self::$connectedDB[$credential] = new Redis($options);
			// Else ...
		}

		return self::$connectedDB[$credential];
	}

	public static function init(){
		$config = Config::load('cache');
		self::$default = &$config['cache.cache_storage'];
		self::$credentials = &$config['cache.storage'];
	}
}
Cache::init();