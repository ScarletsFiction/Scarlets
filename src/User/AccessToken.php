<?php
/*
---------------------------------------------------------------------------
| User Authentication
---------------------------------------------------------------------------
|
| When using this library, make the table structure have 'user_id', 'username', 'password', 'email'
|
*/

namespace Scarlets\User;
use Scarlets\Library\Crypto;
use Scarlets\Library\Cache;
use Scarlets\Library\Database;
use Scarlets\Config;

class AccessToken{
	/*
		AccessToken Object Structure (Keep it simple to reduce token length)
		{
			appID,
			tokenID,
			userID,
			expiration (UTC timestamp)
		}
	*/
	public static $appID = 0;
	public static $tokenID = 0;
	public static $userID = 0;
	public static $expiration = 0;
	public static $permissions = '';

	public static $error = false;

	// Database config reference
	public static $db = false;
	public static $config = false;
	private static $driver = 'database';
	private static $token_table = '';
	private static $app_table = '';

	public static function init(){
		$config = &Config::load('auth')['auth.access_token'];
		if(isset($config['driver']) === true)
			self::$driver = &$config['driver'];

		if(self::$driver === 'database'){
			self::$db = Database::connect($config['database']);

			// If the token_table was not found
			self::$db->onTableMissing($config['token_table'], function(){
				self::$db->createTable($config['token_table'], [
					'token_id' => ['bigint(19)', 'primary', 'key', 'AUTO_INCREMENT'],
					'app_id' => 'int(11)',
					'user_id' => 'int(11)',
					'expiration' => 'int(11)',
					'permissions' => ['text', 'COLLATE', 'latin1_swedish_ci']
				]);
			});

			// If the app_table was not found
			self::$db->onTableMissing($config['app_table'], function(){
				self::$db->createTable($config['app_table'], [
					'app_id' => ['bigint(19)', 'primary', 'key', 'AUTO_INCREMENT'],
					'app_secret' => ['text', 'COLLATE', 'latin1_swedish_ci']
				]);
			});

			self::$token_table = &$config['token_table'];
			self::$app_table = &$config['app_table'];
		}

		elseif(self::$driver === 'redis'){
			self::$db = Cache::connect($config['database']);
			self::$token_table = $config['token_table'].':';
			self::$app_table = $config['app_table'].':';
		}

		// permissions: '|0|12|2|5|' - Must be started and closed with '|'
		self::$config['permissions'] = &$config['permissions'];
	}

	public static function parseAvailableToken(){
        if(isset($_REQUEST['access_token']))
        	return self::parse($_REQUEST['access_token']);

		$headers = '';
        if(isset($_SERVER['HTTP_AUTHORIZATION'])) // Nginx or FastCGI
            $headers = $_SERVER['HTTP_AUTHORIZATION'];

		elseif(isset($_SERVER['Authorization']))
            $headers = $_SERVER['Authorization'];

        elseif(function_exists('apache_request_headers')){
            $requestHeaders = apache_request_headers();

            if(isset($requestHeaders['Authorization']))
                $headers = $requestHeaders['Authorization'];
        }

        if(stripos($headers, 'bearer ') === 0)
            return self::parse(substr($headers, 7));

        return false;
	}

	public static function parse($accessToken){
		$temp = explode('|', Crypto::decrypt($accessToken, false, false, true));
		if(count($temp) !== 4){
			self::$error = 'invalid';
			return false;
		}

		// Expand structure
		self::$appID = $temp[0]+0;
		self::$tokenID = $temp[1]+0;
		self::$userID = $temp[2]+0;
		self::$expiration = $temp[3]+0;

		if(self::$driver === 'redis')
			$expiration = self::$db->hGetAll(self::$token_table."$temp[0]:$temp[2]:$temp[1]");
		else
			$expiration = self::$db->get(self::$token_table, ['expiration', 'permissions'], [
				'token_id'=>self::$tokenID, 'user_id'=>self::$userID
			]);

		if(!$expiration || $expiration['expiration'] <= time()){
			self::$error = 'expired';
			return false;
		}

		self::$permissions = &$expiration['permissions'];
		return true;
	}

	public static function isAllowed($pemissionID){
		self::$config['permissions'] = &self::$permissions;
		if(self::$config['permissions'] === '|*|') return true;

		for($i=0; $i < count(self::$config['permissions']); $i++){
			if(self::$config['permissions'][$i] === $pemissionID){
				return strpos(self::$config['permissions'], "|$i|") !== false; // Permissions -> |0|12|
			}
		}

		return false;
	}

	public static function allowedPermission(){
		if(self::$permissions === '|*|')
			return ['all'];
		
		self::$config['permissions'] = array_filter(explode('|', self::$permissions));
		foreach (self::$config['permissions'] as &$value) {
			$value = self::$config['permissions'][$value];
		}

		return self::$config['permissions'];
	}

	// 'expires_in' in seconds
	public static function refresh($expires_in = 2592000){
		if(!isset(self::$tokenID))
			return ['error'=>'Access token was not found'];

		self::$expiration = time() + $expires_in;

		$obj = [
			'expiration'=>self::$expiration,
			'permissions'=>self::$permissions
		];

		if(self::$driver === 'redis'){
			$key = self::$token_table.self::$appID.':'.self::$userID.':'.self::$tokenID;
			self::$db->hmSet($key, $obj);
			self::$db->expire($key, $expires_in);
		}
		else self::$db->update(self::$token_table, $obj, ['token_id'=>self::$tokenID]);

		// Simplify structure
		return Crypto::encrypt(implode('|', [
			self::$appID,
			self::$tokenID,
			self::$userID,
			self::$expiration
		]), false, false, true);
	}

	// Access token will valid for one day
	// $userData = {userID, username, permissions}
	public static function create($appID, $appSecret, $userData){
		// Verify AppID and Secret Token
		if(self::$driver === 'redis'){
			$app = self::$db->hmGet(self::$app_table.$appID, ['app_secret']);
			if($appSecret !== $app['app_secret'])
				return;
		}
		else $app = self::$db->get(self::$app_table, ['app_id'], ['app_id'=>$appID, 'app_secret'=>$appSecret]);

		if(!$app) return false;

		// Clean old access token
		if(self::$driver === 'database')
			self::$db->delete(self::$token_table, ['app_id'=>$appID, 'user_id'=>$userData['userID']]);

		self::$appID = &$appID;
		self::$tokenID = 0;
		self::$userID = &$userData['userID'];
		self::$expiration = time() + 86400;
		self::$permissions = &$userData['permissions'];

		if(self::$driver === 'redis'){
			self::$tokenID = $tokenID = time();
			$key = self::$token_table."$appID:$userData[userID]:$tokenID";
			self::$db->hmSet($key, [
				'expiration'=>self::$expiration,
				'permissions'=>self::$permissions
			]);
			self::$db->expire($key, self::$expiration);
		}
		else {
			self::$tokenID = self::$db->insert(self::$token_table, [
				'app_id'=>self::$appID,
				'user_id'=>self::$userID,
				'expiration'=>self::$expiration,
				'permissions'=>self::$permissions
			], true);
		}

		// Simplify structure
		return Crypto::encrypt(implode('|', [
			self::$appID,
			self::$tokenID,
			self::$userID,
			self::$expiration
		]), false, false, true);
	}

	// Delete access token from database
	public static function revoke($appID, $userID = '*', $tokenID = '*'){
		if($appID === false && $userID === false){
			$appID = self::$appID;
			$userID = self::$userID;
		}

		if(self::$driver === 'redis')
			self::$db->unlink(self::$token_table."$appID:$userID:$tokenID", $obj);
		else {
			$where = ['app_id'=>$appID, 'user_id'=>$userID];
			if($tokenID !== '*')
				$where['token_id'] = &$tokenID;

			self::$db->delete(self::$token_table, $where);
		}
	}
}

AccessToken::init();