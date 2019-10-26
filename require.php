<?php 

/*
---------------------------------------------------------------------------
| Scarlets Framework
---------------------------------------------------------------------------
|
| This framework is developed by ScarletsFiction and all of it's
| contributor to make a scalable and high performance application.
|
| This is the initialization for loading Scarlets Framework.
| You can use your own autoloader like composer or directly
| include this file.
|
*/
use Scarlets\Config;
use Scarlets\Route;
use Scarlets\Internal;

class Scarlets{
	public static $isConsole = false;
	public static $isWebsite = false;
	public static $interactiveCLI = false;
	public static $maintenance = false;
	
	/*
		> Website Initialization
		Call this method at very first to use this framework
		as website router
	*/
	public static function Website(){
		include_once __DIR__."/src/Route.php";
		Route\Handler::Initialize();
		self::$isWebsite = true;

		// Check if the public folder is relative
		if(Config::$data['app.url_path'] !== false)
			Route\Query::$home = &Config::$data['app.url_path'];

		// Register app middleware
		\App\Middleware::register();

		// Load statuses router first as HTTP fallback
		include_once self::$registry['path.app']."/routes/status.php";

		// Parse received json data if exist
		if(!self::$isConsole){
			if(file_exists(self::$registry['path.maintenance_file']) === true){
				Route\Serve::status(503, true);
				self::$maintenance = true;
			}

			// Put to $_POST because it's usually been send from POST method
			if(isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], 'application/json') !== false)
				$_POST = $_REQUEST = json_decode(file_get_contents('php://input'), true);

			header("X-Framework: Scarlets");

			// Reroute last slash
			if(Config::$data['app.sensitive_web_route'] === false){
				$requestURI = explode('?', $_SERVER['REQUEST_URI'], 2);
				if(substr($requestURI[0], -1, 1) === '/' && $requestURI[0] !== '/'){
					$matched = true;
					$_SERVER['REQUEST_URI'] = substr($requestURI[0], 0, -1);

					if(isset($requestURI[1]))
						$_SERVER['REQUEST_URI'] .= "?$requestURI[1]";
				}
			}

			// Apply custom request method
			if(isset($_REQUEST['_method'])){
				$_REQUEST = $_POST;
				$_SERVER['REQUEST_METHOD'] = $_REQUEST['_method'];

				// Copy to $_GET variable
				if($_SERVER['REQUEST_METHOD'] === 'GET')
					$_GET = $_POST;
			}
			elseif(isset($_SERVER["CONTENT_TYPE"])) {
				if($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'PUT'){
					$contentType = explode(';', $_SERVER["CONTENT_TYPE"]);
					if($contentType[0] === 'multipart/form-data'){
						$contentType = trim(explode('boundary=', $contentType[1])[1]);
						if($contentType){
							$received = file_get_contents('php://input');
							$received = Route\Parser::formDataRequest($contentType, $received);
							$_REQUEST = $_POST = &$received['post'];
							$_FILES = &$received['file'];
						}
					}
				}
			}
		}

		Route\Handler::security();

		try{
			// Include required router
			if(self::$maintenance === true && self::$isConsole === false)
				Route\Serve::maintenance();

			include_once self::$registry['path.app']."/routes/api.php";
			include_once self::$registry['path.app']."/routes/web.php";
		} catch(Internal\ExecutionFinish $f) {
			echo($f->data);
		} catch(\Error $e){
			Scarlets\Error::handleError($e);
		}
	}

	/*
		> Console Initialization
		Call this method at very first to use this framework
		as console handler
	*/
	public static function Console(){
		include_once __DIR__."/src/Console.php";

		// Include required router
		include_once self::$registry['path.app']."/routes/console.php";

		if(!Scarlets\Console::isConsole()){
			die("Scarlets Console can only being called on console window");
		}
		self::$isConsole = true;

		Scarlets\Console::Initialization();
	}

	/*
		> Listen to shutdown event
		Here you can register a event handler on shutdown

		(function) function
	*/
	public static function onShutdown($function, $first = true){
		if(!isset(self::$registry['shutdown'])){
			function beforeShutdown(){
				$list = &Scarlets::$registry['shutdown'];

				try{
					foreach ($list as &$function)
						if($function()) exit;
				} catch(Internal\ExecutionFinish $e) {
					exit;
				}
			}

			self::$registry['shutdown'] = [$function];
			register_shutdown_function('beforeShutdown');
		}
		else{
			if($first)
				array_unshift(self::$registry['shutdown'], $function);
			else
				self::$registry['shutdown'][] = $function;
		}
	}

	/*
		This registry store required data for the framework
		to be used globally for scarlet library. This registry
		is volatile (deleted when process exit)
	*/
	public static $registry = [
		'path.framework'=>__DIR__,
		'Route'=>[]
	];

	/*
		> Registry Execute
		This function is used for executing function
		referenced on the registry
	
		(keys) Can be array if it's very deep
	*/
	public static function registryExec($keys, $params = []){
		if(is_array($keys)){
			$ref = &self::$registry;
			for($i=0; $i < count($keys); $i++){
				if(isset($ref[$keys[$i]]))
					$ref = &$ref[$keys[$i]];
				else
					return null;
			}
			return call_user_func_array($ref, $params);
		} else return call_user_func_array(self::$registry[$keys], $params);
	}

	public static function AppClassLoader($class){
		if(substr($class, 0, 4) === 'App\\')
	    	include self::$registry['path.app_controller'].'/'.str_replace('\\', '/', substr($class, 4)).'.php';

		elseif(substr($class, 0, 9) === 'Scarlets\\')
	    	include self::$registry['path.framework.src'].'/'.str_replace('\\', '/', substr($class, 9)).'.php';
	}

	public static function Initialization(){
		// Get the project root directory
		$path = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		foreach($path as &$value){
			if($value['function'] !== 'include_once')
				continue;
			if(strpos($value['file'], 'root.php') !== false){
				$path = dirname($value['file']);
				break;
			}
		}

		$reg = &self::$registry;
		$reg['path.app'] = $path;
		$reg['path.public'] = "$path/public";
		$reg['path.app_controller'] = "$path/app";
		$reg['path.views'] = "$path/resources/views";
		$reg['path.lang'] = "$path/resources/lang";
		$reg['path.plate'] = "$path/resources/plate";
		$reg['path.maintenance_file'] = "$path/config/_maintenance.mode";
		$reg['path.logs'] = "$path/storage/logs";

		$reg['path.framework.src'] = __DIR__.'/src';
		$reg['path.framework.library'] = __DIR__.'/src/Library';
		
		include_once __DIR__."/src/Error.php";

		// Initialize configuration
		$config = Config::load('app');

		if(!isset($_SERVER['REQUEST_URI'])){
			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['REQUEST_URI'] = '/';
		}

		if($config['app.url_path'] !== false)
			$_SERVER['REQUEST_URI'] = explode($config['app.url_path'], $_SERVER['REQUEST_URI'])[1];
	}
}

spl_autoload_register('\\Scarlets::AppClassLoader');

include_once __DIR__."/src/Config.php";
include_once __DIR__."/src/Internal.php";

// Handle uncaught error on shutdown
Scarlets::onShutdown(function(){
	$httpCode = http_response_code();

	// Take the higher status code
	if(Route::$statusCode > $httpCode)
		$httpCode = Route::$statusCode;

	// Trigger HTTP status Callback
	if(!Scarlets::$isConsole)
		Route\Serve::status($httpCode);

	$error = error_get_last();
	Scarlets\Error::$triggerErrorPage = true;
    if($error && (!isset(Scarlets::$registry['error']) || !Scarlets::$registry['error']))
		Scarlets\Error::ErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
});

// Framework initialization
Scarlets::Initialization();

/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
| Write less dynamic class, and use namespace or static class.
| Scarlets library data can be stored on the registry.
| \Scarlets::$registry['LibrayName'] = ["data"=>"here"];
|
| To maintain code readability, you can separate some files.
|
*/