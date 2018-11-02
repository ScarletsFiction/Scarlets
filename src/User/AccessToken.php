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
		AccessToken Structure
		{
			appID:0,
			tokenID:0,
			userID:0,
			username:'me',
			expiration:0,
			permissions:'|0|12|2|5|' // Must be started and closed with '|'
		}
	*/
	public static $data = [];
	public static $db = false;
	public static $token_table = false;
	public static $app_table = false;
	public static $permissions = false;
	public static $error = false;

	public static function init(){
		$config = &Config::load('auth')['auth.access_token'];
		self::$db = Database::connect($config['database']);
		self::$permissions = $config['permissions'];
		self::$token_table = $config['token_table'];
		self::$app_table = $config['app_table'];
	}

	public static function parse($accessToken){
		$temp = json_decode(Crypto::decrypt($accessToken), true);
		if(!$temp || !isset($temp['tokenID'])){
			self::$error = 'invalid';
			return false;
		}

		$expiration = self::$db->get(self::$token_table, ['user_id', 'expiration'], ['token_id'=>$temp['tokenID']]);
		if(!$expiration || $expiration['expiration'] <= time() || $temp['userID'] != $expiration['user_id']){
			self::$error = 'expired';
			return false;
		}

		self::$data = $temp;
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

	public static function refresh($addionalSeconds = 2678400){
		self::$data['expiration'] = time() + $addionalSeconds;

		self::$db->update(self::$token_table, [
			'expiration'=>self::$data['expiration'],
			'permissions'=>self::$data['permissions']
		], ['token_id'=>self::$data['tokenID']]);

		return Crypto::encrypt(json_encode(self::$data));
	}

	// Access token will valid for one day
	// $userData = {userID, username, permissions}
	public static function create($appID, $appSecret, $userData){
		// Verify AppID and Secret Token
		$app = self::$db->get(self::$app_table, ['app_id'], ['app_id'=>$appID, 'app_secret'=>$appSecret]);
		if(!$app) return false;

		// Clean old access token
		self::$db->delete(self::$app_table, ['app_id'=>$appID, 'user_id'=>$userData['userID']]);

		self::$data = [
			'appID'=>$appID,
			'tokenID'=>0,
			'userID'=>$userData['userID'],
			'username'=>$userData['username'],
			'expiration'=>time() + 86400,
			'permissions'=>$userData['permissions']
		];

		self::$data['tokenID'] = self::$db->insert(self::$token_table, [
			'app_id'=>$appID,
			'user_id'=>$userData['userID'],
			'expiration'=>self::$data['expiration'],
			'permissions'=>$userData['permissions']
		]);

		return Crypto::encrypt(json_encode(self::$data));
	}
}

AccessToken::init();