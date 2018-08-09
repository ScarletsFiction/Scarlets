<?php 

namespace Scarlets;
use \Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Config
---------------------------------------------------------------------------
|
| Any configuration can be accessed here
|
*/

class Config{
	public static $data = [];

	public static function load($filename){
		$frame = &Scarlets::$registry;
		$path = $frame['path.app']."/config/$filename.php";
		if(file_exists($path)){
			$config = &self::$data;

			$data = include $path;
			foreach($data as $key => $value){
				$config[$filename.'.'.$key] = $value;
			}
			return true;
		}
		return false;
	}

	public static function set($file, $key, $value){
		self::$data[$file.$key] = $value;
	}

	public static function &get($file, $key){
		return self::$data[$file.$key];
	}
}

/*
---------------------------------------------------------------------------
| Notice for contributor
---------------------------------------------------------------------------
|
| Make sure you know how to do micro-optimization when developing
| PHP library. For example Regex is slower on PHP rather string
| manipulation, but faster on Javascript.
|
| Here you can compare each PHP function: http://phpbench.com/
|
| Having too many function call or too many classes could be slow.
| Array type on PHP is more faster rather than Object
| You can evaluate it from 'Test_PHPInterpreter.php'
| But on Javascript, Object is the clear winner.
|
| Iterating with long variable reference also slower
| for(loop)
| 	$data->key->array[$i];
|
| The efficient way is saving the reference first
| $ref = $data->key->array;
| for(loop)
| 	$ref[$i];
|
| Because it's slower then you should implement the efficient method.
|
*/