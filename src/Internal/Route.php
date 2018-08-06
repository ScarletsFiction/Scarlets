<?php

namespace Scarlets\Route;
use \Scarlets;

class Group{
	public function group(){

	}
}

class Handler{
	public static function Initialize(){

		// If this running on console
		if(\Scarlets::$isConsole){
			$requests = ['GET', 'POST', 'PUT', 'DELETE'];
			foreach($requests as &$method){
				Scarlets::$registry['Route'][$method] = [];
			}
		}
	}

	public static function register($method, &$path, &$function){
		Scarlets::$registry['Route'][$method][$path] = $function;
	}
}

class Serve{
	public static function view($path, $values = []){
		$path = Scarlets::$registry['path.views'].str_replace('.', '/', $path).'.php';
		$g = &$_GET;
		$p = &$_POST;
		$q = 'Scarlets\Route\Query';
		foreach ($values as $key => $value)
			${$key} = $value;
		include $path;
	}

	public static function plate($path, $values=[]){

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
}