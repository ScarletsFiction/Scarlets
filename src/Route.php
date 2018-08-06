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
		if(!is_callable($func))
			return; // ToDo: Handle router redirect

		if(\Scarlets::$isConsole)
			Handler::register('GET', $url, $value);

		// Instant execute
		elseif($_SERVER['REQUEST_METHOD'] === 'GET') {
			if($url === $_SERVER['REQUEST_URI'])
				$func();
		}
		//isset($_SERVER['HTTPS'])
	}
	
	public static function post($url, $func){
		if(is_callable($func) && $_SERVER['REQUEST_METHOD'] !== 'POST')
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
	
	public static function httpRedirect($URL, $method='get', $data=false)
	{
		// GET method
		if(strtolower($method) === 'get'){
			if($data)
				header('Location: '.explode('?', $URL)[0].'?'.http_build_query($data));
			else header('Location: '.$URL);
		}

		// POST method
		else {
			?><!DOCTYPE html><html><head></head><body>
			<form id="autoForm" action="<?php echo(sanitizeURL($url));?>" method="post">
			<?php 
				if($data) foreach ($data as $key => $value) {
					echo('<input type="hidden" name="'.sanitizeText($key).'" value="'.sanitizeText($value).'">');
				}
			?>
			</form>
			<script type="text/javascript">document.getElementById('autoForm').submit();</script></body></html><?php
		}
		exit;
	}
}