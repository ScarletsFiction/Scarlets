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
	public static $data = [];

	// Database config reference
	public static $db = false;
	public static $token_table = false;
	public static $app_table = false;

	// permissions: '|0|12|2|5|' // Must be started and closed with '|'
	public static $permissions = false;
	public static $error = false;

	public static function init(){
		$config = &Config::load('auth')['auth.access_token'];
		self::$db = Database::connect($config['database']);
		self::$permissions = $config['permissions'];
		self::$token_table = $config['token_table'];
		self::$app_table = $config['app_table'];
	}

	public static function parseAvailableToken(){
        if(isset($_REQUEST['access_token']))
        	return self::parse($_REQUEST['access_token']);

		$headers = '';
        if(isset($_SERVER['HTTP_AUTHORIZATION'])) // Nginx or FastCGI
            $headers = $_SERVER["HTTP_AUTHORIZATION"];

		elseif(isset($_SERVER['Authorization']))
            $headers = $_SERVER["Authorization"];

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
		$temp = explode('|', Crypto::decrypt($accessToken), true);
		if(count($temp) !== 4){
			self::$error = 'invalid';
			return false;
		}

		// Expand structure
		$temp = [
			'appID'=>$temp[0],
			'tokenID'=>$temp[1],
			'userID'=>$temp[2],
			'expiration'=>$temp[3]
		];

		$expiration = self::$db->get(self::$token_table, ['user_id', 'expiration', 'permissions'], ['token_id'=>$temp['tokenID']]);
		if(!$expiration || $expiration['expiration'] <= time() || $temp['userID'] != $expiration['user_id']){
			self::$error = 'expired';
			return false;
		}

		self::$data['permissions'] = &$expiration['permissions'];
		self::$data = &$temp;
		return true;
	}

	public static function isAllowed($action){
		$permissions = &self::$data['permissions'];
		if($permissions === '|*|') return true;

		for($i=0; $i < count($permissions); $i++){
			if($permissions[$i] === $action){
				return strpos($permissions, "|$i|") !== false; // Permissions -> |0|12|
			}
		}

		return false;
	}

	public static function allowedPermission(){
		if(AccessToken::$data['permissions'] === '|*|')
			return ['all'];
		
		$permissions = array_filter(explode('|', AccessToken::$data['permissions']));
		foreach ($permissions as &$value) {
			$value = AccessToken::$permissions[$value];
		}

		return $permissions;
	}

	// 'expires_in' in seconds
	public static function refresh($expires_in = 2592000){
		self::$data['expiration'] = time() + $expires_in;

		self::$db->update(self::$token_table, [
			'expiration'=>self::$data['expiration'],
			'permissions'=>self::$data['permissions']
		], ['token_id'=>self::$data['tokenID']]);

		// Simplify structure
		return Crypto::encrypt(implode('|', [
			self::$data['appID'],
			self::$data['tokenID'],
			self::$data['userID'],
			self::$data['expiration']
		]));
	}

	// Access token will valid for one day
	// $userData = {userID, username, permissions}
	public static function create($appID, $appSecret, $userData){
		// Verify AppID and Secret Token
		$app = self::$db->get(self::$app_table, ['app_id'], ['app_id'=>$appID, 'app_secret'=>$appSecret]);
		if(!$app) return false;

		// Clean old access token
		self::$db->delete(self::$token_table, ['app_id'=>$appID, 'user_id'=>$userData['userID']]);

		self::$data = [
			'appID'=>$appID,
			'tokenID'=>0,
			'userID'=>$userData['userID'],
			'expiration'=>time() + 86400,
			'permissions'=>$userData['permissions']
		];

		self::$data['tokenID'] = self::$db->insert(self::$token_table, [
			'app_id'=>$appID,
			'user_id'=>$userData['userID'],
			'expiration'=>self::$data['expiration'],
			'permissions'=>$userData['permissions']
		]);

		// Simplify structure
		return Crypto::encrypt(implode('|', [
			self::$data['appID'],
			self::$data['tokenID'],
			self::$data['userID'],
			self::$data['expiration']
		]));
	}
}

AccessToken::init();