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

class Scarlets{
	public static $isConsole = false;
	public static $isWebsite = false;
	
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

		// Parse received json data if exist
		if(!self::$isConsole){
			$jsonRequest = file_get_contents('php://input');
			if($jsonRequest)
				$_POST = json_decode($jsonRequest, true);
		}

		header("X-Framework: ScarletsFiction");

		try{
			// Include required router
			include_once self::$registry['path.app']."/routes/status.php";
			include_once self::$registry['path.app']."/routes/api.php";
			include_once self::$registry['path.app']."/routes/web.php";
		} catch(\ExecutionFinish $f) {
			echo($f->data);
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
	public static function onShutdown($function){
		if(!isset(self::$registry['shutdown'])){
			function beforeShutdown(){
				if(!isset(Scarlets::$registry['shutdown']))
					return;

				$list = Scarlets::$registry['shutdown'];
				foreach ($list as &$function)
					$function();

				Scarlets::$registry['shutdown'];
			}

			self::$registry['shutdown'] = [$function];
			register_shutdown_function('beforeShutdown');
		}
		else self::$registry['shutdown'][] = $function;
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
		$reg['path.public'] = $path.'/public';
		$reg['path.app_controller'] = $path.'/app';
		$reg['path.views'] = $path.'/resources/views';
		$reg['path.lang'] = $path.'/resources/lang';
		$reg['path.plate'] = $path.'/resources/plate';
		$reg['path.app.storage'] = $path.'/storage/app';
		$reg['path.cache'] = $path.'/storage/framework/cache';
		$reg['path.sessions'] = $path.'/storage/framework/sessions';
		$reg['path.view_cache'] = $path.'/storage/framework/views';
		$reg['path.logs'] = $path.'/storage/logs';

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

// Handle uncaught error on shutdown
Scarlets::onShutdown(function(){
	$httpCode = http_response_code();

	// Take the higher status code
	if(Route::$statusCode > $httpCode)
		$httpCode = Route::$statusCode;

	// Trigger HTTP status Callback
	Route\Serve::status($httpCode);

	$error = error_get_last();
    if($error && (!isset(Scarlets::$registry['error']) || !Scarlets::$registry['error']))
		Scarlets\Error::ErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
});

// For throwing an finish event on the middle of execution
class ExecutionFinish extends Exception{
	public $data;
	public function __construct($data = false){
		$this->data = $data;
	}
}

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