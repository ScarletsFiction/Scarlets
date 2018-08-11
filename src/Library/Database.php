<?php
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Database\SQL;

class Database{
	public static $connectedDB = [];
	public static $default = false;
	public static $credentials = false;

	/*
		> Connect
		This function will return the connected database handler.
		If the database haven't connected, it will automatically
		connect.
	
		(credential) CredentialID that configured on the configuration
	*/
	public static function &connect($credential=false){
		if($credential === false)
			$credential = self::$default;

		// Check if connected
		if(isset(self::$connectedDB[$credential]))
			return self::$connectedDB[$credential];
		else {
			self::$connectedDB[$credential] = new SQL();
		}
	}

	public static function init(){
		$config = Config::load('database');
		self::$default = &$config['database.default'];
		self::$credentials = &$config['database.credentials'];
	}
}
Database::init();