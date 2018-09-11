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
		} else {
			Scarlets\Route::$uri = $_SERVER['REQUEST_URI'];
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
	public static function view($path, $values = []){
		if(Scarlets::$isConsole && !self::$headerSent)
			self::httpStatus(200);

		$path = Scarlets::$registry['path.views'].'/'.str_replace('.', '/', $path).'.php';
		$g = &$_GET;
		$p = &$_POST;
		$q = 'Scarlets\Route\Query';

		foreach ($values as $key => $value)
			${$key} = $value;
		include $path;
	}

	public static function plate($path, $values=[]){

	}

	public static function status($statusCode){
		if(self::$headerSent) return;
		http_response_code($statusCode);
		
		$router = &Scarlets::$registry['Route']['STATUS'];

		if(isset($router[$statusCode])){
			self::$headerSent = true;
			$router[$statusCode]();
		}
	}

	public static function raw($text){
		$type = gettype($text);
		if($type === 'string')
			print_r($text);
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

	// Returning true value will cancel the current request
	public static function callMiddleware($text){
		$name = explode(':', $text, 2);
		$data = [];

		if(count($name) !== 1)
			$data = explode(',', $name[1]);
		$name = $name[0];

		// Priority the user defined middleware
		if(isset(self::$register[$name])){
			return call_user_func_array(self::$register[$name], $data);
		}

		// Then check for build-in middleware
		elseif(is_callable('self::'.$name)){
			return call_user_func_array('self::'.$name, $data);
		}

		else {
			Scarlets\Error::warning('Middleware for "'.$name.'" was not defined');
			return true;
		}
	}

	private static function throttle($request = 1, $timer = 3){
		return;
	}
}