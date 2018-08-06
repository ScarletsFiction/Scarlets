<?php 

namespace Scarlets;

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
	// This can avoid multiple process of single error
	public static $lastError = [];

	public static function warning($message){
		if(!\Scarlets::$registry['config']['app.debug']){

		}
	}

	// Error handler, passes flow over the exception logger with new ErrorException.
	public static function ErrorHandler($severity, $message, $file, $line){
	    if(!(error_reporting() & $severity))
	        return; // This error code is not included in error_reporting

	    // Check if Error already processed
	    if(in_array($file.$line, self::$lastError))
	    	return;

	    // Check if error over limit
	    if(count(self::$lastError)==2)
	    	array_shift(self::$lastError);

	    // Save to last error
	    self::$lastError[] = $file.$line;
	    
	    if(isset(\Scarlets::$registry['error']) && \Scarlets::$registry['error']) return;
	    $reg = &\Scarlets::$registry;
	    
	    if(!$reg['console']) $reg['error'] = true;
	    
	    $trace = explode("\nStack trace:", $message);
	    if(count($trace) === 1){
			$exception = new \ErrorException($message, 0, $severity, $file, $line);
			$trace = $exception->getTraceAsString();
	    } else {
	    	$message = $trace[0];
	    	$trace = $trace[1];
	    }

	    $breakline = $reg['console'] ? '':' <br>';
	    $url = 'Scarlets Console';
	    if(!isset($_SERVER['SERVER_NAME']) && !$reg['console']){
	    	$breakline = '';
	    	$url = "Startup Handler";
	    }
	    else if(!$reg['console'])
	    	$url = "http".(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

$message = "Exception type: ".self::ErrorType($severity).";$breakline
Message: $message;$breakline
File: $file;$breakline
Line: $line;$breakline
URL: $url$breakline
Trace: $trace;$breakline$breakline\n\n";

		$appConfig = &$reg['config'];

		$exitting = true;
		$warningAsError = $appConfig['app.warning_as_error'];
		if($warningAsError && ($severity === E_WARNING
			|| $severity === E_CORE_WARNING
			|| $severity === E_COMPILE_WARNING
			|| $severity === E_COMPILE_WARNING
			))
			$exitting = false;

		elseif($severity === E_NOTICE
			|| $severity === E_USER_NOTICE
			|| $severity === E_DEPRECATED
			|| $severity === E_USER_DEPRECATED)
			$exitting = false;


		$method = $appConfig['app.log'];
		if($method === 'single')
			0; // ToDo: Save to file log

		if($exitting && !$appConfig['app.debug']){
			echo('Under Maintenance<br><br>Please refresh your browser to take changes<br>Or contact StefansArya if you still receive this message (≧▽≦)／');
			die(0); // ToDo: Send 500 status and static error page
		} else print($message);
	}

	public static function logMessage(){

	}

	public static function simpleHTML(){

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
	    return "E_UNDEFINED"; 
	}
}

// Always show error on travis
if(getenv('CI')){
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
} else {
	ini_set('display_errors', 0);
	ini_set('log_errors', 0);
	set_error_handler(['\\Scarlets\\Error', "ErrorHandler"], E_ALL);
}
ini_set('error_reporting', E_ALL);