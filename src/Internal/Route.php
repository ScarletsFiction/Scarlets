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
			foreach($opts as &$value){
				if(strpos($value, 'name:') !== false){
					$name[] = $value;
					unset($value);
				}
			}

			if(count($opts) === 0)
				$opts = false;
		}
		Scarlets::$registry['Route'][$method][$path] = [$function, $opts];

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

	public static function httpCode($statusCode){
		if(self::$headerSent) return;
		http_response_code($statusCode);
		self::$headerSent = true;
		
		$router = &Scarlets::$registry['Route']['STATUS'];
		$router[$statusCode]();
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