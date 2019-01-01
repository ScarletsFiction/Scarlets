<?php
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Database\SQL;

class Database{
	public static $connectedDB = [];
	public static $default = false;
	public static $credentials = false;

	/**
	 * Connect with database credential
	 * 
	 * This function will return the connected database handler.
	 * If the database haven't connected, it will automatically
	 * connect.
	 *
	 * @param string $credential CredentialID that configured on the configuration
	 * @return \Scarlets\Interfaces\Database\SQL
	 */
	public static function &connect($credential=false){
		// Use default credential if not specified
		if($credential === false)
			$credential = self::$default;

		// Return the connected database
		if(isset(self::$connectedDB[$credential]))
			return self::$connectedDB[$credential];

		// Get reference of requested database credential
		$ref = &self::$credentials[$credential];
		$copy = false;

		// Check if it's referenced to other credential
		if(isset(self::$credentials[$credential]['connection'])){
			$requestedCredential = $credential;
			$credential = $ref['connection'];

			// Try to obtain copy after connected
			$copy = true;
		}

		// Connect if not connected
		if(!isset(self::$connectedDB[$credential])){
			$options = &self::$credentials[$credential];
			$driver = &$options['driver'];

			// MySQL
			if($driver === 'mysql'){
				self::$connectedDB[$credential] = new SQL($options);

				// Obtain the clone
				if($copy){
					$copy = clone $db;
					$copy->change($ref);
					self::$connectedDB[$requestedCredential] = &$copy;
					return $copy;
				}
			}
			elseif($driver === 'pgsql'){
				if($copy){
					$options = &self::$connectedDB[$requestedCredential];
					unset($options['connection']);

					foreach (self::$credentials[$credential] as $key => $value) {
						if(!isset($options[$key]))
							$options[$key] = $value;
					}
					self::$connectedDB[$requestedCredential] = new SQL($options);
				}
				else self::$connectedDB[$credential] = new SQL($options);

				// Remove prefix
				self::$connectedDB[$credential]->change(false);
			}
			// Else ...
		}

		$db = &self::$connectedDB[$copy ? $requestedCredential : $credential];
		return $db;
	}

	public static function init(){
		$config = Config::load('database');
		self::$default = &$config['database.default'];
		self::$credentials = &$config['database.credentials'];
	}
}
Database::init();