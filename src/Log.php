<?php 
namespace Scarlets;
use \Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Log
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

// Shortcut for logging
function log($msg){
	Log::message($msg);
}

class Log{
	public static $path = '';
	public static $type = '';
	public static function init(){
		self::$type = &Config::$data['app.log'];
		if(self::$type === 'errorlog')
			self::$path = Scarlets::$registry['path.app'].'/error.log';
	}

	public static function message($msg){
		if(self::$type === 'errorlog')
			file_put_contents(self::$path, 'Log: '.print_r($msg, true)."\n---\n", FILE_APPEND);
	}

	public static function trace($msg = ''){
		$e = new \Exception();

		if(self::$type === 'errorlog')
			file_put_contents(self::$path, "Trace (".print_r($msg, true)."): ".str_replace(self::$path, '', $e->getTraceAsString())."\n---\n", FILE_APPEND);
	}

	public static function broke($msg){
		while(ob_get_level()){
			ob_get_clean();
		}
		print_r($msg);
		exit;
	}
}
Log::init();