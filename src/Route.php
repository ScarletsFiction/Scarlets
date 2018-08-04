<?php 

namespace Scarlets;
include_once __DIR__."/Internal/Route.php";

/*
---------------------------------------------------------------------------
| Scarlets Route
---------------------------------------------------------------------------
|
| Description haven't added
|
*/
class Route{
	public static function get($url, $func){
		if(is_callable($func) &&  $_SERVER['REQUEST_METHOD'] !== 'GET')
			return;

		if(\Scarlets::$registry['standalone'])
			Handler::register('GET', $url, $value);
		else {
			if($url === $_SERVER['REQUEST_URI'])
				$func();
		}
		//isset($_SERVER['HTTPS'])
	}
	
	public static function post($url, $func){
		if(is_callable($func) &&  $_SERVER['REQUEST_METHOD'] !== 'GET')
			return;
	}
	
	public static function delete(){
		
	}
	
	public static function put(){
		
	}
	
	public static function options(){
		
	}
	
	public static function namespaces(){
		return new Route\Group();
	}
	
	public static function domain(){
		return new Route\Group();
	}
	
	public static function prefix(){
		return new Route\Group();
	}
	
	public static function name(){
		return new Route\Group();
	}
	
	public static function middleware(){
		return new Route\Group();
	}
	
	public static function view($url, $name, $data){
		
	}
	
	public static function redirect($from, $to, $httpStatus){
		
	}
}