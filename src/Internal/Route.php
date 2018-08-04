<?php

namespace Scarlets\Route;
use \Scarlets;

class Group{
	public function group(){

	}
}

class Handler{
	public static function Initialize(){
		$requests = ['GET', 'POST', 'PUT', 'DELETE'];
		foreach($requests as &$method){
			Scarlets::$registry['Route'][$method] = [];
		}
	}

	public static function register($method, &$path, &$function){
		Scarlets::$registry['Route'][$method][$path] = $function;
	}
}

class Serve{
	public static function view($path, $_ = []){
		$path = Scarlets::$registry['path.views'].str_replace('.', '/', $path).'.php';
		$g = &$_GET;
		$p = &$_POST; // Use this for more performance
		$q = 'Scarlets\Route\Query';
		include $path;
	}

	public static function plate($path, $values=[]){

	}
}

class Query{
	public static function &get($key){
		if(isset($_GET[$key]))
			return $_GET[$key];
		$temp = null;
		return $temp;
	}

	public static function &post($key){
		if(isset($_POST[$key]))
			return $_POST[$key];
		$temp = null;
		return $temp;
	}
}