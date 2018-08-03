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
class Scarlets{
	/*
		> Website Initialization
		Call this method at very first to use this framework
		as website router
	*/
	public static function Website(){
		include_once __DIR__."/src/Router.php";
	}

	/*
		> Console Initialization
		Call this method at very first to use this framework
		as console handler
	*/
	public static function Console(){
		include_once __DIR__."/src/Console.php";
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
	public static $registry = ['framework_path'=>__DIR__, 'full_url'=>''];

	/*
		> Registry Execute
		This function is used for executing function
		referenced on the registry
	
		(keys) Can be array if it's very deep
	*/
	public static function registryExec($keys){
		$ref = &self::$registry;

		if(is_array($keys)){
			for($i=0; $i < count($keys); $i++){ 
				if(isset($ref[$keys[$i]]))
					$ref = &$ref[$keys[$i]];
				else
					return null;
			}
			return $ref();
		} else return $ref[$keys]();
	}
}

include_once __DIR__."/src/Config.php";
include_once __DIR__."/src/Error.php";

// Handle uncaught error on shutdown
Scarlets::onShutdown(function(){
    $isError = false;
    
    if($error = error_get_last()){
    	$type = $error['type'];
    	if($type === E_ERROR || $type === E_PARSE || $type === E_CORE_ERROR || $type === E_COMPILE_ERROR)
	        $isError = true;
    }

    if($isError)
		Scarlets\Error::ErrorHandler($error['type'], $error['message'], $error['file'], $error['line'], true);
});

// Framework initialization (volatile)
Scarlets::$registry['Initialize'] = function(){

	// Get the project root directory
	$path = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file']);
	Scarlets::$registry['app_path'] = $path;

	if(isset($_SERVER['HTTP_HOST']))
		Scarlets::$registry['domain'] = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'];

	// Initialize configuration
	$configPath = $path.'/config';
	if(!Scarlets\Config::Path($configPath))
		return;

	unset(Scarlets::$registry['Initialize']);
}; Scarlets::registryExec('Initialize');

include_once __DIR__."/src/Loader.php";