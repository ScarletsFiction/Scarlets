<?php
namespace Scarlets\User;
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\User\Session;
use \Scarlets\Library\Database;
use \Scarlets\Extend\Strings;

class Auth{
	public static $database = false;
	public static $table = false;

	public static function init(){
		$config = Config::load('auth');
		self::$database = Database::connect($config['auth.database']);
		self::$table = Database::connect($config['auth.table']);
	}

	//Return true if not logged in
	public static function logout(){
		$sifyData = &Session::$sifyData;
		$data = &Session::$data;

		if(isset($sifyData['userID']) && $sifyData['userID'] !== '' && $data['userID'] === $sifyData['userID'])
			$data['oldAccount'] = $data['userID'];
		else
			$data = [];

		$sifyData = [];
		$_COOKIE = [];
		Session::saveSifyData();
	}

	//Return true if success
	public static function login($username, $password)
	{
		$username = strtolower($username);
		$data = self::$database->get('users', ['user_id', 'name', 'password'], ['username'=>$username]);

		if($data !== false && password_verify($password, $data['password']))
		{
			$sifyData = &Session::$sifyData;
			$data = &Session::$data;

			$data['username'] = &$username;
			$data['userID'] = &$data['user_id'];
			$data['name'] = &$data['name'];

			$sifyData['username'] = &$username;
			$sifyData['userID'] = &$data['user_id'];
			$sifyData['shard'] = &$_COOKIE['shard'];
			saveSifyData();

			return true;
		}
		return false;
	}
	//Return [userID, username] if logged in
	public static function checkLoginStatus($returnBoolOnly = false)
	{
		$sifyData = &Session::$sifyData;
		$data = &Session::$data;

		// Check if cookie and session data have userID
		if(isset($data['userID']) && isset($sifyData['userID']))
		{
			// Check if exist but different userID
			if($data['userID'] && $sifyData['userID'] && $data['userID'] !== $sifyData['userID']){
				Session::destroyCookies();
				return false;
			}

			// Check if zero or empty
			else if(!$data['userID'] || !$sifyData['userID'])
				return false;
			
			// All ok
			else{
				if($returnBool) return true;
				else return [
					'userID'=>$data['userID'],
					'username'=>$data['username']
				];
			}
		}
		else return false;
	}

	// $data = [userID, email, username, name, password]
	// return [success(bool), message/userID]
	public static function signUp($data)
	{
		// Validate email
		if(strlen(filter_var($data['email'], FILTER_SANITIZE_EMAIL)) < strlen($data['email'])-1)
			return [false, 'Email not valid'];

		// Validate username
		if(strlen(preg_replace('/[^a-zA-Z0-9]/', '', $data['username'])) < strlen($data['username']))
			return [false, 'Username not valid'];

		// Check for existance
		$temp = self::$database->get('users', ['user_id'], [
			'OR'=>[
				'username'=>$data['username'],
				'email[~]'=>'|'.$data['email']
			]
		]);
		if($temp !== false){
			if($temp['username'] === $data['username'])
				return [false, 'Username already used'];
			return [false, 'Email already used'];
		}

		$userID = self::$database->insert('users', $data, 'userID');
		return [true, $userID];
	}

	public static function isUsernameExist($username)
	{
		if(self::$database->get('users', ['user_id'], ['username'=>$username]) !== false)
			return true;
		return false;
	}

	public static function isEmailExist($email)
	{
		if(self::$database->get('users', ['user_id'], ['email[~]'=>$email]) !== false)
			return true;
		return false;
	}
}
Auth::init();