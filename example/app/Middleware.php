<?php
namespace App;
use \Scarlets\Route\Serve;
use \Scarlets\Library\Cache;

class Middleware{
	public static function register(){
		$currentClass = get_class();
		$list = get_class_methods($currentClass);
		foreach ($list as $function) {
			if($function === 'register')
				continue;

			\Scarlets\Route\Middleware::$register[$function] = $currentClass.'::'.$function;
		}
	}

	public static function html($type = 'public'){
	    if($type === 'public'){
	        // Pending all output
	        ob_start();

	        // On request finished
	        return function(){
	            $body = ob_get_clean();

	            // Output the body with header and footer
	            Serve::view('static.header', [
	                'title'=>'Home'
	            ]);
	            Serve::raw($body);
	            Serve::view('static.footer');

	            // Skip other routes
	            Serve::end();
	        };
	    }

	    throw new Exception("Middleware 'html:".$type."' was not registered");
	}

	public static function origin($allowed = '*'){
	    if($allowed === '*')
	    	$allowed = $_SERVER['HTTP_ORIGIN'];
	    else
	    	$allowed = str_replace('|', ',', $allowed);

		if(isset($_SERVER['HTTP_ORIGIN'])){
			$setHeader = function($text){
				if(\Scarlets::$isConsole)
					\Scarlets\Library\Server::setHeader($text);
				else header($text);
			};
			
		    $setHeader('Access-Control-Allow-Origin: '.$allowed);
		    $setHeader('Access-Control-Allow-Credentials: true');
		    $setHeader('Access-Control-Max-Age: 86400');

			// Skip server process if javascript only send header for checking
			if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
			    if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
			        $setHeader('Access-Control-Allow-Methods: GET, POST, OPTIONS');
			    if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
			        $setHeader('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
			    exit(0);
			}
		}
	}

	public static function limit($request = 2, $seconds = 30){
	    $total = Cache::get('request.limit', 0);

	    if($total < $request){
	        // Set expiration when it's the first request only ($total == 0)
	        $expire = $total === 0 ? $seconds : 0;

	        // Put the request count on cache
	        Cache::set('request.limit', $total + 1, $expire);

	        // Continue request
	        return false;
	    }

	    // Block request
	    else{
	        Serve::status(404);
	        return true;
	    }
	}
}