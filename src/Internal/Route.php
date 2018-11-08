<?php

namespace Scarlets\Route;
use \Scarlets;

class Handler{
	public static $Extension = false;
	public static function Initialize(){
		// Create some reference to registry
		Scarlets\Route::$instantOutput = &Scarlets\Config::$data['app.instant'];

		// If this running on console
		if(Scarlets::$isConsole){
			$requests = ['GET', 'POST', 'PUT', 'DELETE'];
			foreach($requests as &$method){
				Scarlets::$registry['Route'][$method] = [];
			}
		}
	}

	public static function initExtension(){
		self::$Extension = new Extension();
		return self::$Extension;
	}

	public static function register($method, &$path, &$function, &$opts = false){
		$name = [];
		if($opts !== false){
			if(is_string($opts))
				$opts = [$opts];

			foreach($opts as &$value){
				if(strpos($value, 'name:') !== false){
					$name[] = substr($value, 5);
					unset($value);
				}
			}

			if(count($opts) === 0)
				$opts = false;
		}

		if($method === 'STATUS')
			Scarlets::$registry['Route'][$method][$path] = $function;
		else{
			if(substr($path, 0, 1) !== '/')
				$path = '/'.$path;
			Scarlets::$registry['Route'][$method][$path] = [$function, $opts];
		}

		foreach ($name as &$value) {
			Scarlets::$registry['Route']['NAME'][$value] = &Scarlets::$registry['Route'][$method][$path];
		}
	}
}

class Serve{
	public static $headerSent = false;
	public static $pending = []; // [func, callable]
	public static $pendingLevel = 0;

	public static function view($path, $values = [], $static = false){
		if($static && isset($_REQUEST['_scarlets']) && strpos($_REQUEST['_scarlets'], '.dynamic.') !== false)
			return;

		if(Scarlets::$isConsole && !self::$headerSent)
			self::status(200);

		// Internal variable
		$path = Scarlets::$registry['path.views'].'/'.str_replace('.', '/', $path).'.php';
		$g = &$_GET;
		$p = &$_POST;

		// Class reference
		$serve = 'Scarlets\Route\Serve';
		$lang = 'Scarlets\Library\Language';
		$q = 'Scarlets\Route\Query';

		// User defined variable
		foreach ($values as $key => $value)
			${$key} = $value;

		include $path;

		// Mark callable pending to true
		if(self::$pendingLevel !== 0)
			self::$pending[self::$pendingLevel][1] = true;
	}

	public static function special($data){
		if(!isset($_REQUEST['_scarlets']) || strpos($_REQUEST['_scarlets'], '.dynamic.') === false)
			return;

		$temp = str_replace('-->', '--|&>', $data);
		echo('<!-- SF-Special:'.json_encode($temp).'-->');
	}

	public static function plate($path){
		if(Scarlets::$isConsole && !self::$headerSent)
			self::status(200);

		$path = Scarlets::$registry['path.plate'].'/'.str_replace('.', '/', $path).'.php';
		include $path;
		
		// Mark callable pending to true
		if(self::$pendingLevel !== 0)
			self::$pending[self::$pendingLevel][1] = true;
	}

	public static function pending($func = false, $childLevel = 0){
		if(is_array($func)){
			$level = self::$pendingLevel - $childLevel;

			$ref = false;
			if(isset(self::$pending[$level]))
				$ref = &self::$pending[$level][2];

			if($ref === false)
				trigger_error("Pending serves was not found (index: ".$level." | total: ".(count(self::$pendingLevel) - 1).")");
				
			$ref = array_merge($ref, $func);
			return;
		}

		ob_start();

		if($func === false)
			$func = function(){};

		// Call this function when any serve is made
		self::$pendingLevel = ob_get_level();
		self::$pending[self::$pendingLevel] = [$func, false, []];
	}

	public static function resume($func = false){
		if(self::$pendingLevel === 0)
			return;

		$data = ob_get_clean();
		$ref = &self::$pending[self::$pendingLevel];

		if(!$ref[1] || !$func){
			unset($ref);
			self::$pendingLevel = ob_get_level();
			return;
		}

		call_user_func($ref[0], $ref[2]);
		print($data);
		$func();

		self::$pendingLevel = ob_get_level();
		if(self::$pendingLevel !== 0)
			self::$pending[self::$pendingLevel - 1][1] = true;
	}

	public static function status($statusCode, $headerOnly = false){
		if(self::$headerSent) return;
		http_response_code($statusCode);

		if($headerOnly) return;
		
		$router = &Scarlets::$registry['Route']['STATUS'];

		if(isset($router[$statusCode])){
			self::$headerSent = true;
			$router[$statusCode](ob_get_level() ?: ob_get_clean());
			exit;
		}
	}

	public static function raw($text){
		$type = gettype($text);
		if($type === 'string')
			print_r($text);

		// Mark callable pending to true
		if(self::$pendingLevel !== 0)
			self::$pending[self::$pendingLevel][1] = true;
	}

	public static function end($text = false, $statusCode = 200){
		if($text !== false)
			self::raw($text);

		for ($i = self::$pendingLevel; $i > 0; $i--) {
			$data = ob_get_clean();
			$ref = &self::$pending[self::$pendingLevel];
			call_user_func($ref[0], $ref[2]);
		}

		self::status($statusCode, true);
		self::$headerSent = true;

		if(!Scarlets::$isConsole)
			throw new \ExecutionFinish();

		self::$pendingLevel = 0;
	}
}

class Query{
	public static function &get($key){
		if(isset($_GET[$key]))
			return $_GET[$key];
		$temp = '';
		return $temp;
	}

	public static function &post($key){
		if(isset($_POST[$key]))
			return $_POST[$key];
		$temp = '';
		return $temp;
	}

	// Public directory path based on app.url_path config
	public static $home = '';
}

class Middleware{
	// Register user defined middleware
	// $register['name'] = function(){}
	public static $register = [];
	public static $pendingArgs = [];
	public static $routerArgs = [];
	public static $pendingData = [];

	// Returning true value will cancel the current request
	public static function callMiddleware($text){
		$name = explode(':', $text, 2);
		$data = [];

		if(count($name) !== 1)
			$data = explode(',', $name[1]);
		$name = $name[0];

		// Priority the user defined middleware
		if(isset(self::$register[$name]))
			return call_user_func_array(self::$register[$name], $data);

		else {
			Scarlets\Error::warning('Middleware for "'.$name.'" was not defined');
			return true;
		}
	}

	public static function pending(){
		self::$pendingArgs = array_merge(self::$pendingArgs, func_get_args());
	}
}