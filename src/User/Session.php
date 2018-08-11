<?php
namespace Scarlets\User;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Crypto;
use \Scarlets\Library\Database;

/*
---------------------------------------------------------------------------
| Scarlets Framework Session Handler
---------------------------------------------------------------------------
|
| This class is required for internal authentication and security library
| SFSession does implement triple session protection for avoid collision
| and other security vulnerability. But using this on unsecured protocol
| will have some chance to be hacked by MIM Attack.
|
| SFSession.id === sifyData.id
| SFSession.ip === sifyData.ip
| sifyData.integrity === SFIntegrity (Not imlemented yet)
|
*/

class Session{
	// Saved on browser
	public static $sifyData = [];

	// Always saved on mysql
	public static $ID = false; // ID Range's depend on your column datatype
	public static $TextID = '';
	public static $FullTextID = '';
	public static $data = [];

	// Will return old IP Address if it's different with last record
	public static $IPChanged = false;

	// Just for comparing when saving
	private static $sifyData_ = [];
	private static $data_ = [];

	private static $database = false;

	// Configuration
	private static $domain = false;
	private static $path = false;
	private static $lifetime = false;
	private static $expire_on_close = false;
	private static $secure = false;
	private static $http_only = false;

	public static function init(){
		// If the client was unable to send cookies but able to send from url request
		if(isset($_POST['SFSessions']) && !isset($_COOKIE['SFSessions']))
			$_COOKIE['SFSessions'] = $_POST['SFSessions'];

		// Load configuration
		$config = Config::load('session');
		self::$domain = &$config['session.domain'];
		self::$path = &$config['session.path'];
		self::$lifetime = &$config['session.lifetime'];
		self::$expire_on_close = &$config['session.expire_on_close'];
		self::$secure = &$config['session.secure'];
		self::$http_only = &$config['session.http_only'];

		// Register database
		if($config['session.driver'] === 'database'){
			self::$database = Database::connect($config['session.credential']);
			self::$database->onTableMissing(function(){
				self::$database->createTable();
			});
		}

		// Execute Session Handler
		if(isset($_COOKIE['SFSessions']))
			self::load();
	}

	// Load sifyData from cookie
	public static function loadSifyData(){
		$ref = &self::$sifyData;
		$ref = self::extractSifyData();
		self::$ID = Crypto::dSify($ref[0]);
		self::$TextID = $ref[0];

		// If sifyData was not found on some sessions, then remake
		if(!$ref[1]){
			self::destroyCookies();
			return;
		}

		$ref = $ref[1];

		// Make a copy
		self::$sifyData_ = $ref;
	}

	// Save sifyData to cookie
	public static function saveSifyData($force=false){
		if(headers_sent()){
			Log::message('Couldn\'t save sifyData because header already sent. Make sure you save it before any body content being sent');
			return;
		}

		if(serialize(self::$sifyData_) !== serialize(self::$sifyData) || $force){
			self::$FullTextID = 
			$sified = self::compileSifyData(self::$TextID, self::$sifyData);

			$domain = explode('.', $_SERVER['HTTP_HOST']);
			if(count($domain)>2)
				unset($domain[0]);

			$domain = implode('.', $domain);
			setcookie('SFSessions', $sified, time()+2419200, '/', $domain, true);
		}
	}

	// Load the session data from browser and database
	public static function load(){
		// Set SFSession data from cookies
		if(self::$ID === false) self::loadSifyData();
		$database = &self::$database;

		// Search session in database
		$data = $database->get('sessions', ['data', 'lastactive', 'blocked', 'ipaddress'], ['id'=>self::$ID]);

		 // Data found in database
		if($data !== false){
			if(!empty(self::$data_)) return;

			self::$data = json_decode($data['data'], true); // Load session data
			self::$data_ = self::$data; // Just for comparing when shutdown

			// Reset after 15 seconds and update some information
			if(time()-15 >= $data['lastactive'])
			{
				if($_SERVER['REMOTE_ADDR'] !== $data['ipaddress'])
					Crypto::$oldIPAddress = $data['ipaddress'];

				$database->update('sessions', [
					'lastactive' => time(),
					'lasturlaccess' => $_SERVER['REQUEST_URI'],
					'ipaddress' => $_SERVER['REMOTE_ADDR'],
				], ['id' => self::$ID]);
			}

			if($data['blocked'] === 1)
				die('Too many request coming from your IP Address. Please verify if you\'re not a bot..');
		}
	}

	public static function save($new = false){
		// Update session on the database
		if($new === false){
			self::$database->update('sessions', [
				'data' => json_encode(self::$data)
			], ['id' => self::$ID]);
		}

		// Write new session to browser and database
		else {
			// Save the session to the database
			$rawID = self::$database->insert('sessions', [
				'session' => $newID,
				'data' => json_encode(self::$data),
				'lasturlaccess' => $_SERVER['REQUEST_URI'],
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
				'lastcreated' => time()
			], 'id');

			self::$ID = $rawID;
			$newID = Crypto::Sify($rawID);
			self::$TextID = $newID;
			self::$sifyData = ['userid'=>''];

			// Write session to browser
			$sified = self::compileSifyData($newID, self::$sifyData);
			$_COOKIE['SFSessions'] = $sified;
			self::saveSifyData(true);
		}
	}
	
	public static function destroyCookies($justCookies=false){
		if(!session_id()) session_start();
		$_SESSION = [];
		session_destroy();

		if(!isset($_SERVER['HTTP_COOKIE'])) {
			$expires = time()-3600;
			$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			$cookies_ = [];
			foreach($cookies as $cookie) {
			    $parts = explode('=', $cookie);
			    $name = trim($parts[0]);
			    if(!array_search($name, $cookies_)!==false) continue;
			    $cookies_[] = $name;

			    setcookie($name, '0', $expires);
			    setcookie($name, '0', $expires, '/');

				$domain = explode('.', $_SERVER['HTTP_HOST']);
				if(count($domain)>2){
					unset($domain[0]);
				}
				$domain = implode('.', $domain);
			    setcookie($name, '0', $expires, '/', $domain);
			    setcookie($name, '0', $expires, '/', $domain, true);
			    if(isset($_COOKIE[$name])) unset($_COOKIE[$name]);
			}
		}

		self::$database->delete('sessions', ['id' => self::$ID]);
		$_COOKIE = [];
		self::$data = [];
		self::$data_ = [];
		self::$ID = false;
		self::$TextID = '';
		self::$sifyData_ = [];
		self::$sifyData = [];
	}

	public static function &extractSifyData($str_=false){
		if($str_ === false) $str = $_COOKIE['SFSessions'];
		else $str = &$str_;

		// Check if there are duplicated cookies
		$data = explode('XpDW2bZ', $str);
		if(count($data) === 1){
			$cookies = explode('SFSessions=', $_SERVER['HTTP_COOKIE']);
			if(count($data) >= 3){
				$cookies = explode(';', $cookies[2])[0];
				$data = explode('XpDW2bZ', $cookies);
			}
		}

		// Decode SifyData
		if(count($data) !== 1){
			$data[1] = Crypto::decrypt($data[1]);

			if($data[1] !== '')
				$data[1] = json_decode(@gzinflate($data[1]), true);
			else $data[1] = [];
		}
		else $data[1] = false;
		return $data;
	}
	
	public static function compileSifyData($textID, $sifyData){
		$data = Crypto::encrypt(gzdeflate(json_encode($sifyData), 9));
		return $textID.'XpDW2bZ'.$data;
	}
}
Session::init();