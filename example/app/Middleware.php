<?php
namespace App;
use \Scarlets\Route;
use \Scarlets\Error;
use \Scarlets\Route\Serve;
use \Scarlets\Library\Cache;
use \Scarlets\Library\Server;
use \Scarlets\Route\Middleware as Mainware;
use \App\Auth\User;

// Database shortcut
class DB{
	/** @var \Scarlets\Library\Database\SQL */
	public static $scarlets = false;
}
DB::$scarlets = Database::connect('scarletsfiction');

class Middleware{
	public static function register(){
		// Get function list on this class
		$currentClass = get_class();
		$list = get_class_methods($currentClass);

		// Register functions on route middleware
		foreach ($list as &$function) {
			if($function === 'register')
				continue;

			Mainware::$register[$function] = "$currentClass::$function";
		}
	}

	public static function auth($type, $scope = 'public', $for = false){
		if($type === 'api'){
			Header::set('Content-type: application/json');

			// Register a callback when any error was happen
			\Scarlets::onShutdown(function(){
				if(Error::$hasError)
					Serve::end('{"error":"Internal server error"}', 500);
			});

			// Only authenticated user who can access
			if($scope === 'private'){
				// Handle user access token here
				User::init();
				self::origin('*');

				// Prevent further execution if not authenticated
				if(User::$id === false)
					Serve::end('{"error":"Authentication failed"}', 401);
			}

			// Public access but limited to your domain (You can read about CORS)
			else
				self::origin(['https://www.mywebsite.com', 'https://my.profile.com']);

			return function(){
				// Output all returned data from function as JSON
				Serve::end(json_encode(Mainware::$pendingData), 200);
			};
		}
	}

	public static function html($type = 'public'){
	    if($type === 'public'){
	        // Pending all output
	        ob_start();

	        // On request finished
	        return function($headerData = [], $footerData = []){
	            $body = ob_get_clean();

	            // This will trigger 'special' router event on ScarletsFrame
	            // When using dynamic route mode
	            Serve::special($headerData);

	            // Output the body with header and footer
	            Serve::view('static.header', $headerData, true);
	            Serve::raw($body);
	            Serve::view('static.footer', $footerData, true);

	            $elapsed = 1;
	            if(\Scarlets::$isConsole)
	            	$elapsed = round(microtime(true) - Server::$requestMicrotime, 5);
	            else
	            	$elapsed = round(microtime(true) - $GLOBALS['startupWebsiteTime'], 5);

        		Serve::raw("\n<benchmark><!-- Dynamic page generated in $elapsed seconds. --></benchmark>");

	            // Skip other routes
	            Serve::end();
	        };
	    }

	    throw new Exception("Middleware 'html:".$type."' was not registered");
	}

	public static function origin($allowed = '*'){
		if(isset($_SERVER['HTTP_ORIGIN'])){
		    if($allowed === '*')
		    	true;
		    elseif(is_array($allowed) && in_array($_SERVER['HTTP_ORIGIN'], $allowed))
		    	true;
		    else
		    	exit;

		    Header::set("Access-Control-Allow-Origin: $_SERVER[HTTP_ORIGIN]");
		    Header::set('Access-Control-Allow-Credentials: true');
		    Header::set('Access-Control-Max-Age: 86400');

			if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
			    Header::set('Access-Control-Allow-Methods: PUT, DELETE, GET, POST, OPTIONS');
			if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
			    Header::set("Access-Control-Allow-Headers: $_SERVER[HTTP_ACCESS_CONTROL_REQUEST_HEADERS]");

			// Skip server process if it's only send options header
			if($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
			    exit;
		}
	}

	public static function limit($request = 2, $seconds = 30){
		$cache = Cache::connect('framework');
	    $total = $cache->get('request.limit', 0);

	    if($total < $request){
	        // Set expiration when it's the first request only ($total == 0)
	        $expire = $total === 0 ? $seconds : 0;

	        // Put the request count on cache
	        $cache->set('request.limit', $total + 1, $expire);

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

class Header{
	public static function set($text){
		if(\Scarlets::$isConsole)
			\Scarlets\Library\Server::setHeader($text);
		else header($text);
	}
}