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

class Log{
	public static $path = '';
	public static $type = '';
	public static function init(){
		self::$type = &Config::$data['app.log'];
		if(self::$type === 'errorlog'){
			self::$path = &Scarlets::$registry['path.app'].'/error.log';
		}
	}

	public static function message($msg){
		if(self::$type === 'errorlog')
			file_put_contents(self::$path, $msg."\n---\n", FILE_APPEND);
	}
}
Log::init();