<?php

/*
---------------------------------------------------------------------------
| SFCache Redis
---------------------------------------------------------------------------
|
| This library can help you connect to Redis database from PHP script.
| Redis is fast for key-value, but you can't filter/match the result
| from redis. It would be a good choice for caching realtime data
| instead be the main database.
|
*/
namespace Scarlets\Library\Cache;

class Redis{
	/** @var \Redis PHPRedis Library */
	public $connection;

	public $type = 'Redis';
	private $database = '';

	public function __construct($options){
		// Default options
		if(!isset($options['host'])) $options['host'] = '127.0.0.1';
		if(!isset($options['username'])) $options['username'] = 'root';
		if(!isset($options['password'])) $options['password'] = '';
		if(!isset($options['port'])) $options['port'] = 6379;
		if(!isset($options['database'])) trigger_error('Redis database index was not specified');
		$this->database = $options['database'];

		$this->connection = new \Redis();
		try{
			$this->connection->connect($options['host'], $options['port']);
			$this->connection->select($this->database);
			$this->connection->auth($options['password']);
			$this->connection->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

			if(isset($options['prefix']) && $options['prefix'] != false)
				$this->connection->setOption(\Redis::OPT_PREFIX, $options['prefix']);
		}catch(\RedisException $e){
			trigger_error($e->getMessage());
		}
	}

	public function get($key, $default=false){
		return $this->connection->get($key) ?: $default;
	}

	public function set($key, $value, $seconds=0){
		$this->connection->set($key, $value);
		if($seconds !== 0)
			$this->connection->expireAt($key, time()+$seconds);
	}

	public function has($key){
		return $this->connection->exists($key);
	}

	public function &pull($key, $default=false){
		$data = $this->connection->get($key);
		$this->connection->delete($key);
		if($data === false) return $default;
	}

	public function forget($key){
		$this->connection->delete($key);
	}

	public function flush($key="*"){
		if($key === '*')
			$this->connection->flushDb();
	}

	public function extendTime($key, $seconds){
		if($seconds === 0)
			return $this->connection->persist($key);
		$this->connection->expireAt($key, time()+$seconds);
	}

	public function &select($database){
		$this->connection->select($database);
		return $this;
	}
}