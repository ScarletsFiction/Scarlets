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
	public static function warning($message){
		if(!\Scarlets::$registry['config']['app']['debug']){

		}
	}

	// Error handler, passes flow over the exception logger with new ErrorException.
	public static function ErrorHandler($severity, $message, $file, $line){
	    if(!(error_reporting() & $severity))
	        return; // This error code is not included in error_reporting

		self::ExceptionHandler(new \ErrorException($message, 0, $severity, $file, $line));
	}

	// Uncaught exception handler.
	public static function ExceptionHandler($e){
		$severity = $e->getSeverity();

$message = "Exception type: ".self::ErrorType($severity)."; <br>
Message: {$e->getMessage()}; <br>
File: {$e->getFile()}; <br>
Line: {$e->getLine()}; <br>
URL: ".\Scarlets::$registry['domain'].$_SERVER['REQUEST_URI']." <br>
Trace: {$e->getTraceAsString()}; <br><br>\n\n";

		$appConfig = \Scarlets::$registry['config']['app'];

		$exitting = true;
		$warningAsError = $appConfig['warning_as_error'];
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


		$method = $appConfig['log'];
		if($method === 'single')
			0; // ToDo: Save to file log

		if($exitting && !$appConfig['debug']){
			echo('Under Maintenance<br><br>Please refresh your browser to take changes<br>Or contact StefansArya if you still receive this message (≧▽≦)／');
			die(0); // ToDo: Send 500 status and static error page
		} else print($message);
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

ini_set('display_errors', 0);
ini_set('log_errors', 0);
ini_set('error_reporting', E_ALL);
set_error_handler(['\\Scarlets\\Error', "ErrorHandler"], E_ALL);