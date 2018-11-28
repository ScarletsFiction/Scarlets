<?php
namespace Scarlets\Library\Cache;

class FileSystem{
	public $path = '';
	public $expiration = [];
	public $expirationPath = '';
	public $lastCheck = 0;

	public function __construct($settings){
		self::$path = $settings['path'].'/cache';
		self::$expirationPath = self::$path.'/__expiration.srz';

		if(!file_exists(self::$path))
			mkdir(self::$path, 0777, true);

		if(!file_exists(self::$expirationPath)){
			file_put_contents(self::$expirationPath, serialize([]));
			self::$expiration = [];
		}
		else
			self::$expiration = unserialize(file_get_contents(self::$expirationPath));

		self::$lastCheck = filemtime(self::$expirationPath);
	}
	
	private function reloadExpiration(){
		$temp = filemtime(self::$expirationPath);
		if(self::$lastCheck < $temp){
			self::$lastCheck = $temp;
			self::$expiration = unserialize(file_get_contents(self::$expirationPath));
		}
	}

	public function &get($key, $default=null){
		if(!isset(self::$expiration[$key]))
			return $default;

		if(self::$expiration[$key] !== 0){
			self::reloadExpiration();
			if(self::$expiration[$key] < time()){
				unset(self::$expiration[$key]);
				unlink(self::$path."/$key.cache");
				file_put_contents(self::$expirationPath, serialize(self::$expiration));
				return $default;
			}
		}

		$data = file_get_contents(self::$path."/$key.cache");
		if(substr($data, 1, 1) === ':' || is_numeric(substr($data, 2, 1)))
			$data = unserialize($data);

		return $data;
	}

	public function set($key, $value, $seconds=0){
		if($seconds !== 0){
			self::$expiration[$key] = $seconds + time();
			file_put_contents(self::$expirationPath, serialize(self::$expiration));
		}

		if(!isset(self::$expiration[$key])){
			self::$expiration[$key] = 0;
			file_put_contents(self::$expirationPath, serialize(self::$expiration));
		}

		if(!is_string($value))
			$value = serialize($value);

		file_put_contents(self::$path."/$key.cache", $value);
	}

	public function has($key){
		if(!isset(self::$expiration[$key]))
			return false;

		if(self::$expiration[$key] !== 0){
			self::reloadExpiration();
			if(self::$expiration[$key] < time()){
				unset(self::$expiration[$key]);
				unlink(self::$path."/$key.cache");
				file_put_contents(self::$expirationPath, serialize(self::$expiration));
				return false;
			}
		}

		return true;
	}

	// Get and forget
	public function &pull($key, $default=null){
		if(!isset(self::$expiration[$key]))
			return $default;

		$data = file_get_contents(self::$path."/$key.cache");
		if(substr($data, 1, 1) === ':' || is_numeric(substr($data, 2, 1)))
			$data = unserialize($data);

		unlink(self::$path."/$key.cache");
		unset(self::$expiration[$key]);
		file_put_contents(self::$expirationPath, serialize(self::$expiration));
		return $data;
	}

	public function forget($key){
		unlink(self::$path."/$key.cache");
		if(!isset(self::$expiration[$key]))
			return false;

		unset(self::$expiration[$key]);
		file_put_contents(self::$expirationPath, serialize(self::$expiration));
	}
	
	public function flush($key){
		self::$expiration = [];
		$list = glob(self::$path.'/*.*');
		foreach($list as &$value){
			unlink($value);
		}
		return true;
	}
	
	public function extendTime($key, $seconds){
		if(!isset(self::$expiration[$key]))
			return false;

		self::reloadExpiration();
		if(self::$expiration[$key] >= time()){
			unset(self::$expiration[$key]);
			unlink(self::$path."/$key.cache");
			file_put_contents(self::$expirationPath, serialize(self::$expiration));
			return false;
		}

		self::$expiration[$key] = $seconds + time();
		file_put_contents(self::$expirationPath, serialize(self::$expiration));
		return true;
	}
}
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
| It's very recommended to have msgpack on your system
| https://github.com/msgpack/msgpack-php
|
*/