<?php 

namespace Scarlets;
use Scarlets;
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
	public static $instantOutput = false;
	public static function get($url, $func){
		if(!is_callable($func))
			return; // ToDo: Handle router redirect

		if(Scarlets::$isConsole)
			Route\Handler::register('GET', $url, $func);

		elseif($_SERVER['REQUEST_METHOD'] === 'GET' && $url === $_SERVER['REQUEST_URI']){ // ToDo: implement regex
			if(self::$instantOutput === false){
				ob_start();
				$func();
				if(Scarlets\Error::$hasError !== true) echo(ob_get_contents());
				else Scarlets\Error::$hasError = false;
				ob_end_clean();
			}
			else $func();
		}
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