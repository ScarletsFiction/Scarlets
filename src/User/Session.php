<?php
namespace Scarlets\User;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\Library\Crypto;
use \Scarlets\Library\Server;
use \Scarlets\Library\Database;

/*
---------------------------------------------------------------------------
| Scarlets Framework Session Handler
---------------------------------------------------------------------------
|
| This class is required for internal authentication and security library
| SFSession does implement dual session protection for avoid collision
| and other security vulnerability. But using this on unsecured protocol
| will have some chance to be stealed by MIM Attack.
|
| SFSession.id === sifyData.id
| SessionDB.lastcreated === sifyData.@created
| sifyData.integrity === SFIntegrity (Not imlemented yet)
|
*/

class Session{
	public static $started = false;

	// Saved on browser
	public static $sify = [];

	// Always saved on mysql
	public static $ID = false; // ID Range's depend on your column datatype
	public static $TextID = '';
	public static $FullTextID = '';
	public static $data = [];

	// Will return old IP Address if it's different with last record
	public static $IPChanged = false;

	// Just for comparing when saving
	private static $sify_ = [];
	private static $data_ = [];

	private static $database = false;
	private static $table = false;

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
			self::$table = &$config['session.table'];

			// If the table was not found
			self::$database->onTableMissing(self::$table, function(){
				self::$database->createTable(self::$table, [
					'id' => ['bigint(19)', 'primary', 'key', 'AUTO_INCREMENT'],
					'session' => 'tinytext',
					'data' => 'text',
					'ipaddress' => 'text',
					'lasturlaccess' => 'text',
					'lastlistingaccess' => ['int(11)', 'default', 0],
					'lastrecordedtime' => ['int(11)', 'default', 0],
					'lastcreated' => ['int(11)', 'default', 0],
					'blocked' => ['tinyint(1)', 'default', 0]
				]);
			});
		}

		// Execute Session Handler
		if(isset($_COOKIE['SFSessions']) && !self::$started)
			self::load();

		// Register session save event
		$saveData = function(){
			if(serialize(self::$data_) !== serialize(self::$data))
				self::save();
		};
		if(Scarlets::$isConsole){
			Server::onComplete($saveData);

			// Reset session data before any request
			Server::onStart(function(){
				$started = false;
				$sify = [];
				$ID = false;
				$TextID = '';
				$FullTextID = '';
				$data = [];
				$IPChanged = false;
				$sify_ = [];
				$data_ = [];

				if(isset($_COOKIE['SFSessions']))
					self::load();
			});
		}
		else
			Scarlets::onShutdown($saveData);
	}

	// Load sifyData from cookie
	public static function loadSifyData(){
		$ref = &self::$sify;
		$ref = self::extractSifyData();
		if($ref === false)
			return false;

		self::$ID = Crypto::dSify($ref[0]);
		self::$TextID = $ref[0];

		$ref = $ref[1];

		// If integrity was different, then destroy it
		// if(isset($ref['integrity']) && $ref['integrity'] !== $_COOKIE['integrity']){
		// 	self::destroyCookies();
		// 	return;
		// }

		// Make a copy
		self::$sify_ = $ref;
		return true;
	}

	// Save sifyData to cookie
	public static function saveSifyData($force=false){
		if(!Scarlets::$isConsole && headers_sent()){
			trigger_error('Couldn\'t save sifyData because header already sent. Make sure you save it before any body content being sent');
			return;
		}

		if(serialize(self::$sify_) !== serialize(self::$sify) || $force){
			self::$FullTextID = 
			$sified = self::compileSifyData(self::$TextID, self::$sify);

			$ref = &self::$domain;
			if(strpos($ref, '@host') !== false){
				$domain = explode('.', $_SERVER['HTTP_HOST']);
				if(count($domain) > 2)
					unset($domain[0]);

				$domain = str_replace('@host', implode('.', $domain), $ref);
			}

			$cookieData = ['SFSessions', $sified, time()+self::$lifetime*60, self::$path, $domain, self::$secure, self::$http_only];
			if(!Scarlets::$isConsole)
				call_user_func_array('setcookie', $cookieData);
			else
				call_user_func_array('Scarlets\Library\Server::setCookie', $cookieData);
		}
	}

	// Load the session data from browser and database
	public static function &load($return = false){
		$false = false;
		if(self::$started){
			if($return === 'server')
				return self::$data;
			elseif($return === 'cookie')
				return self::$sify;
			return $false;
		}

		self::$started = true;

		// Set SFSession data from cookies
		if(self::$ID === false && !self::loadSifyData())
			return $false;
		$database = &self::$database;

		// Search session in database
		$data = $database->get(self::$table, ['data', 'lastrecordedtime', 'blocked', 'ipaddress', 'lastcreated'], ['id'=>self::$ID]);

		// Data found in database
		if($data !== false){
			if(!empty(self::$data_)) return $false;

			// Check creation date
			if(!isset(self::$sify['@created']) || $data['lastcreated'] !== self::$sify['@created']){
				self::destroyCookies();
				self::$started = false;
				$temp = self::load($return);
				return $temp;
			}

			self::$data = json_decode($data['data'], true); // Load session data
			self::$data_ = self::$data; // Just for comparing when shutdown

			// Reset after 15 seconds and update some information
			if(time()-15 >= $data['lastrecordedtime'])
			{
				if($_SERVER['REMOTE_ADDR'] !== $data['ipaddress'])
					Crypto::$oldIPAddress = $data['ipaddress'];

				$database->update(self::$table, [
					'lastrecordedtime' => time(),
					'lasturlaccess' => $_SERVER['REQUEST_URI'],
					'ipaddress' => $_SERVER['REMOTE_ADDR'],
				], ['id' => self::$ID]);
			}

			if($data['blocked'] === 1)
				die('Too many request coming from your IP Address. Please verify if you\'re not a bot..');
		}
		else {
			self::destroyCookies();
			self::$started = false;
			$temp = self::load($return);
			return $temp;
		}
		return $false;
	}

	public static function save($new = false){
		// Update session on the database
		if($new === false){
			self::$database->update(self::$table, [
				'data' => json_encode(self::$data)
			], ['id' => self::$ID]);
		}

		// Write new session to browser and database
		else {
			$created = time();
			$rawID = rand(1,9999).strrev(time());
			self::$ID = $rawID;

			// Convert ID to text
			$newID = Crypto::Sify($rawID);
			self::$TextID = $newID;

			// Save the session to the database
			self::$database->insert(self::$table, [
				'id' => $rawID,
				'session' => $newID,
				'data' => json_encode(self::$data),
				'lasturlaccess' => $_SERVER['REQUEST_URI'],
				'ipaddress' => $_SERVER['REMOTE_ADDR'],
				'lastcreated' => $created
			]);

			self::$sify = ['@created' => $created];

			// Save sifyData
			self::saveSifyData(true);
			return [$newID, self::$sify];
		}
	}

	public static function destroyCookies($justCookies=false){
		if(isset($_SERVER['HTTP_COOKIE'])){
			$expires = time()-3600;
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            $cookies_ = [];
            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                if(array_search($name, $cookies_) !== false) continue;
                $cookies_[] = $name;

                setcookie($name, '', $expires);
                setcookie($name, '', $expires, '/');

                $domain = explode('.', $_SERVER['HTTP_HOST']);
                if(count($domain)>2)
                    unset($domain[0]);

                $domain = implode('.', $domain);
                setcookie($name, '', $expires, '/', $domain);
                setcookie($name, '', $expires, '/', $domain, true);
                if(isset($_COOKIE[$name])) unset($_COOKIE[$name]);
            }
		}

		self::$database->delete(self::$table, ['id' => self::$ID]);
		$_COOKIE = [];
		self::$data = [];
		self::$data_ = [];
		self::$ID = false;
		self::$TextID = '';
		self::$sify_ = [];
		self::$sify = [];
	}

	public static function &extractSifyData($str_=false){
		if(!isset($_COOKIE['SFSessions'])){
			if(empty(self::$data))
				$data = false;
			else
				$data = self::save(true);
			return $data;
		}

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
			$data[1] = Crypto::decrypt($data[1], false, false, true);

			if($data[1] !== '')
				$data[1] = json_decode($data[1], true);
			else $data[1] = [];
		}
		else $data[1] = false;
		return $data;
	}

	public static function compileSifyData($textID, $sify){
		$data = Crypto::encrypt(json_encode($sify), false, false, true);
		return $textID.'XpDW2bZ'.$data;
	}
}
Session::init();