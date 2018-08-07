<?php

namespace Scarlets\Route;
use \Scarlets;

class Group{
	public function group(){

	}
}

class Handler{
	public static function Initialize(){
		// Create some reference to registry
		Scarlets\Route::$instantOutput = &Scarlets::$registry['config']['app.instant'];

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

	public static function register($method, &$path, &$function){
		Scarlets::$registry['Route'][$method][$path] = $function;
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

	public static function httpStatus($statusCode, $header=[]){
		echo "HTTP/1.1 $statusCode OK\n";
		$headers = [
			"Content-Type" => "text/html"
		];
		$headers = array_merge($headers, $header);
		foreach($headers as $key => $value){
			echo $key.': '.$value."\n";
		}
		echo "\r\n\r\n";
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