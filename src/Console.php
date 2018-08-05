<?php 

namespace Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Console
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

class Console{

	/* 
		> Registered Commands

		$commands[firstword] = [
			[[arg0], callback1], // firstword {0}
			[[arg0, arg1], callback2] // firstword {0} {1}
		];
	*/
	public static $commands = [];

	public static function Initialization(){
		
	}

	public static function interactiveShell(){
		self::clear();
		echo("Welcome to ScarletsFramework!\n\n");
		$fp = fopen("php://stdin","r");

		while(1)
			if(self::interpreter(rtrim(fgets($fp, 1024))))
		    	break;

		fclose($fp);
		exit;
	}

	public static function interpreter(&$line){

	}

	/*
		> Command Register
		Description here
	
		(pattern) ..
		(callback) ..
	*/
	public static function command($pattern, $callback){
		$pattern = explode(' ', $pattern);
		$firstword = $pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));

		self::$commands[$firstword][] = [&$pattern, &$callback];
	}

	public static function clear(){
		if(strncasecmp(PHP_OS, 'win', 3) === 0)
			popen('cls', 'w');
		else exec('clear');
	}

	public static function isConsole(){
	    if(defined('STDIN'))
	        return true;

	    if(php_sapi_name() === 'cli')
	        return true;

	    if(array_key_exists('SHELL', $_ENV))
	        return true;

	    if(empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) 
	        return true;

	    if(!array_key_exists('REQUEST_METHOD', $_SERVER))
	        return true;

	    return false;
	}
}