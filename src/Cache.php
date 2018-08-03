<?php 

namespace Scarlets;

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

	// This will be deleted on exit
	public static $volatile = [];

	// This will be saved on shutdown and loaded when startup
	public static $keep = [];

	public static function &get($key){
		# code...
	}

	public static function set($key, $value){
		# code...
	}
}

/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
|  - Don't use JSON for serialize cache because it's slow on large data
|  - Don't use native serialize for user input because it's exploitable
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