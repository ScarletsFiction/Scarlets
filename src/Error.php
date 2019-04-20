<?php 
namespace Scarlets;
use \Scarlets;
use \Scarlets\Log;
use \Scarlets\Route\Serve;

/*
---------------------------------------------------------------------------
| Scarlets Error
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

class Error{
	// Only save 2 error file path and line
	// This can avoid multiple error of single error
	public static $lastError = [];
	public static $hasError = false;
	public static $triggerErrorPage = false;

	public static $currentError = [];

	public static function warning($message){
		trigger_error($message, E_USER_WARNING);
	}

	// Available when using single log
	public static function lastError($time){

	}

	// Error handler, passes flow over the exception logger with new ErrorException.
	public static function ErrorHandler($severity, $message, $file, $line){
		if(E_RECOVERABLE_ERROR === $severity){
		    throw new \ErrorException($message, $severity, 0, $file, $line);
		    return;
		}

		while(ob_get_level())
			ob_get_clean();

	    if(!(error_reporting() & $severity))
	        return; // This error code is not included in error_reporting

	    self::$hasError = true;

	    // Send error status if it's a website
	    if(Scarlets::$isWebsite)
	    	Serve::status(500, true);

	    // Check if Error already processed
	    if(in_array($file.$line, self::$lastError))
	    	return;

	    // Check if error over limit
	    if(count(self::$lastError)==2)
	    	array_shift(self::$lastError);

	    // Save to last error
	    self::$lastError[] = $file.$line;
	    
	    if(isset(Scarlets::$registry['error']) && Scarlets::$registry['error']) return;
	    $reg = &Scarlets::$registry;
	    
	    if(!Scarlets::$isConsole) $reg['error'] = true;
	    
	    $trace = explode("\nStack trace:", $message);
	    if(count($trace) === 1){
			$exception = new \ErrorException($message, 0, $severity, $file, $line);
			$trace = $exception->getTraceAsString();
	    } else {
	    	$message = $trace[0];
	    	$trace = $trace[1];
	    }

		$appConfig = &\Scarlets\Config::$data;

	    if($appConfig['app.simplify_trace']){
	    	$trace = str_replace($reg['path.framework'], '{Framework}', $trace);
	    	$file = str_replace($reg['path.framework'], '{Framework}', $file);
	    	$trace = str_replace($reg['path.app'], '{AppRoot}', $trace);
	    	$file = str_replace($reg['path.app'], '{AppRoot}', $file);
	    	$trace = str_replace('[internal function]', '{System}', $trace);
	    }

	    $trace = explode('Scarlets\Error::ErrorHandler', $trace);
	    $trace = count($trace) === 1 ? $trace[0] : $trace[1];

	   	$trace = explode('Scarlets\Error::warning', $trace);
	    $trace = count($trace) === 1 ? $trace[0] : $trace[1];

	   	$trace = explode('trigger_error', $trace);
	    $trace = count($trace) === 1 ? $trace[0] : $trace[1];

	   	$trace = explode(': Scarlets\Library\Server::request', $trace)[0];

	    $trace = explode("\n", $trace, 2)[1];

	    // Reset index
	    $trace = explode("\n#", $trace);
	    $i = count($trace)-1;
	    foreach ($trace as &$value) {
	    	$value = "#$i ".explode(' ', $value, 2)[1];
	    	$i--;
	    }
	    $trace = implode("\n", $trace);

	    $breakline = Scarlets::$isConsole ? '':' <br>';
	    $url = 'Scarlets Console';
	    if(!isset($_SERVER['SERVER_NAME']) && Scarlets::$isConsole){
	    	$breakline = '';
	    	$url = 'Startup Handler';
	    }
	    else if(isset($_SERVER['REQUEST_URI']))
	    	$url = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		$message = 'Exception type: '.self::ErrorType($severity).";\n".
		"Message: $message;\n".
		"File: $file;\n".
		"Line: $line;\n".
		"URL: $url".
		($trace != '' ? "\nTrace: \n$trace;" : ';');

		self::$currentError[] = $message;

		// die($message);

		if(!$appConfig['app.debug'] && Scarlets::$isWebsite){
			Log::message($message);
			if(!self::$triggerErrorPage) exit;

			Serve::status(500);
			exit;
		}
		else{
			if(Scarlets::$isConsole)
				print($message."\n\n");
			else{
				print(str_replace("\n", "<br>\n", $message)."<br><br>\n\n");
				exit;
			}
		}
	}

	public static function &getUnreadError(){
		$temp = self::$currentError;
		self::$currentError = [];
		self::$hasError = false;
		return $temp;
	}

	// http://php.net/manual/en/errorfunc.constants.php
	public static function ErrorType($type)
	{
	    if($type === E_ERROR) return 'E_ERROR';
	    elseif($type === E_WARNING) return 'E_WARNING';
	    elseif($type === E_PARSE) return 'E_PARSE';
	    elseif($type === E_NOTICE) return 'E_NOTICE';
	    elseif($type === E_CORE_ERROR) return 'E_CORE_ERROR';
	    elseif($type === E_CORE_WARNING) return 'E_CORE_WARNING';
	    elseif($type === E_COMPILE_ERROR) return 'E_COMPILE_ERROR';
	    elseif($type === E_COMPILE_WARNING) return 'E_COMPILE_WARNING';
	    elseif($type === E_USER_ERROR) return 'E_USER_ERROR';
	    elseif($type === E_USER_WARNING) return 'E_USER_WARNING';
	    elseif($type === E_USER_NOTICE) return 'E_USER_NOTICE';
	    elseif($type === E_STRICT) return 'E_STRICT';
	    elseif($type === E_RECOVERABLE_ERROR) return 'E_RECOVERABLE_ERROR';
	    elseif($type === E_DEPRECATED) return 'E_DEPRECATED';
	    elseif($type === E_USER_DEPRECATED) return 'E_USER_DEPRECATED';
	    return 'E_UNDEFINED';
	}

	public static function checkUncaughtError(){
		$error = error_get_last();
	    if($error && (!isset(Scarlets::$registry['error']) || !Scarlets::$registry['error']))
			self::ErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
	}
}

// Shortcut for logging
function log($msg){
	Log::message($msg);
}

// Always show error on travis
if(getenv('CI'))
	ini_set('display_errors', 1);
else {
	ini_set('display_errors', 0);
	set_error_handler(['\\Scarlets\\Error', "ErrorHandler"], E_ALL);
}
ini_set('error_reporting', E_ALL);

// Catch fatal error
ini_set('log_errors', 1);
ini_set('error_log', Scarlets::$registry['path.app'].'/error.log');