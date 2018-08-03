<?php 

namespace Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Route
---------------------------------------------------------------------------
|
| Description haven't added
|
*/
class Route{
	public static function get($url, $value){
		
	}
	
	public static function post(){
		
	}
	
	public static function delete(){
		
	}
	
	public static function put(){
		
	}
	
	public static function options(){
		
	}
	
	public static function namespaces(){
		return new RouteGroup();
	}
	
	public static function domain(){
		return new RouteGroup();
	}
	
	public static function prefix(){
		return new RouteGroup();
	}
	
	public static function name(){
		return new RouteGroup();
	}
	
	public static function middleware(){
		return new RouteGroup();
	}
	
	public static function view($url, $name, $data){
		
	}
	
	public static function redirect($from, $to, $httpStatus){
		
	}
}

class RouteGroup{
	public function group(){

	}
}