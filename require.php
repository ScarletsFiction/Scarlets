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

	}

	/*
		> Console Initialization
		Call this method at very first to use this framework
		as console handler
	*/
	public static function Console(){

	}

	/*
		This registry store required data for the framework
		to be used globally for scarlet library. This registry
		is volatile (deleted when process exit)
	*/
	public static $registry = ['root_path'=>__DIR__];
}

include_once __DIR__."/src/Config.php";
include_once __DIR__."/src/Cache.php";
include_once __DIR__."/src/Library.php";