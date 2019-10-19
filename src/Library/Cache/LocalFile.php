<?php
namespace Scarlets\Library\Cache;

class LocalFile{
	public $path = '';
	public $expiration = [];
	public $expirationPath = '';
	public $lastCheck = 0;

	public function __construct($settings){
		$this->path = "$settings[path]";
		$this->expirationPath = $this->path.'/__expiration.srz';

		if(!file_exists($this->path))
			mkdir($this->path, 0777, true);

		if(!file_exists($this->expirationPath)){
			file_put_contents($this->expirationPath, serialize([]));
			$this->expiration = [];
		}
		else
			$this->expiration = unserialize(file_get_contents($this->expirationPath));

		$this->lastCheck = filemtime($this->expirationPath);
	}
	
	private function reloadExpiration(){
		$temp = filemtime($this->expirationPath);
		if($this->lastCheck < $temp){
			$this->lastCheck = $temp;
			$this->expiration = unserialize(file_get_contents($this->expirationPath));
		}
	}

	public function &get($key, $default=null){
		if(!isset($this->expiration[$key]))
			return $default;

		if($this->expiration[$key] !== 0){
			$this->reloadExpiration();
			if($this->expiration[$key] < time()){
				unset($this->expiration[$key]);
				unlink($this->path."/$key.cache");
				file_put_contents($this->expirationPath, serialize($this->expiration));
				return $default;
			}
		}

		$data = file_get_contents($this->path."/$key.cache");
		if(substr($data, 1, 1) === ':')
			$data = unserialize($data);

		return $data;
	}

	public function set($key, $value, $seconds=0){
		if($seconds !== 0){
			$this->expiration[$key] = $seconds + time();
			file_put_contents($this->expirationPath, serialize($this->expiration));
		}

		if(!isset($this->expiration[$key])){
			$this->expiration[$key] = 0;
			file_put_contents($this->expirationPath, serialize($this->expiration));
		}

		if(!is_string($value))
			$value = serialize($value);

		file_put_contents($this->path."/$key.cache", $value);
	}

	public function has($key){
		if(!isset($this->expiration[$key]))
			return false;

		if($this->expiration[$key] !== 0){
			$this->reloadExpiration();
			if($this->expiration[$key] < time()){
				unset($this->expiration[$key]);
				unlink($this->path."/$key.cache");
				file_put_contents($this->expirationPath, serialize($this->expiration));
				return false;
			}
		}

		return true;
	}

	// Get and forget
	public function &pull($key, $default=null){
		if(!isset($this->expiration[$key]))
			return $default;

		$data = file_get_contents($this->path."/$key.cache");
		if(substr($data, 1, 1) === ':')
			$data = unserialize($data);

		unlink($this->path."/$key.cache");
		unset($this->expiration[$key]);
		file_put_contents($this->expirationPath, serialize($this->expiration));
		return $data;
	}

	public function forget($key){
		unlink($this->path."/$key.cache");
		if(!isset($this->expiration[$key]))
			return false;

		unset($this->expiration[$key]);
		file_put_contents($this->expirationPath, serialize($this->expiration));
	}
	
	public function flush($key="*"){
		$this->expiration = [];
		$list = glob($this->path."/$key.*");
		foreach($list as &$value){
			unlink($value);
		}
		return true;
	}
	
	public function extendTime($key, $seconds){
		if(!isset($this->expiration[$key]))
			return false;

		$this->reloadExpiration();
		if($this->expiration[$key] >= time()){
			unset($this->expiration[$key]);
			unlink($this->path."/$key.cache");
			file_put_contents($this->expirationPath, serialize($this->expiration));
			return false;
		}

		$this->expiration[$key] = $seconds + time();
		file_put_contents($this->expirationPath, serialize($this->expiration));
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