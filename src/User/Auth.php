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
use \Scarlets;
use \Scarlets\Config;
use \Scarlets\User\Session;
use \Scarlets\Library\Database;
use \Scarlets\Extend\Strings;

class Auth{
	public static $database = false;
	public static $table = false;

	public static $sessions = false;
	public static $cookies = false;

	public static function init(){
		$config = &Config::load('auth')['auth.users'];
		self::$database = Database::connect($config['database']);
		self::$table = $config['table'];

		if(!Session::$started)
			Session::load();
	}

	public static function logout(){
		$sify = &Session::$sify;
		$data = &Session::$data;

		if(isset($sify['userID']) && $sify['userID'] !== '' && $data['userID'] === $sify['userID'])
			$data['oldAccount'] = $data['userID'];
		else
			$data = [];

		$sify = [];
		$data = [];
		$_COOKIE = [];
		$_SESSION = [];
		Session::saveSifyData();
	}

	// $where = ['where' => 'true']
	// $middleware = [['extra', 'column'], CallbackFunction($row)]
	// if CallbackFunction return false, then the login will failed
	// Return true if success
	public static function login($username, $password, $where = false, $middleware = false)
	{
		$username = strtolower($username);
		$column = ['user_id', 'password'];
		$where_ = ['username'=>$username];

		// Email
		if(filter_var($username, FILTER_VALIDATE_EMAIL)){
			$where_ = ['email[~]'=>';'.$username];
			$column[] = 'username';
		}

		// Merge extra SQL statement
		if($where)
			$where_ = array_merge($where_, $where);
		if(is_array($middleware) && is_array($middleware[0]))
			$column = array_merge($column, $middleware[0]);

		$row = self::$database->get(self::$table, $column, $where_);

		// Call middleware if exist
		if(is_array($middleware) && is_callable($middleware[1]) && !$middleware[1]($row))
			return false;
		elseif(is_callable($middleware) && !$middleware($row))
			return false;

		// Verify user password then save it to user sessions
		if($row !== false && password_verify($password, $row['password']))
		{
			if(isset($row['username']))
				$username = $row['username'];
			$sify = &Session::$sify;
			$data = &Session::$data;

			$data['username'] = &$username;
			$data['userID'] = &$row['user_id'];

			$sify['username'] = &$username;
			$sify['userID'] = &$row['user_id'];
			Session::saveSifyData();

			return true;
		}
		return false;
	}

	// Return {userID, username} if logged in
	public static function getLoginData($returnBool = false)
	{
		$sify = &Session::$sify;
		$data = &Session::$data;

		// Check if cookie and session data have userID
		if(isset($data['userID']) && isset($sify['userID']))
		{
			// Check if exist but different userID
			if($data['userID'] && $sify['userID'] && $data['userID'] !== $sify['userID']){
				Session::destroyCookies();
				return false;
			}

			// Check if zero or empty
			else if(!$data['userID'] || !$sify['userID'])
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

	// $data = [email, username, password]
	// If you want to insert more data, you can update
	// the rows with user_id after this return true
	// return [true, user_id]
	// return [false, 'error message']
	public static function register($data)
	{
		// Validate email
		if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
			return [false, 'Email not valid'];

		// Validate username
		if(strlen(preg_replace('/[^a-zA-Z0-9]/', '', $data['username'])) < strlen($data['username']))
			return [false, 'Username not valid'];

		// Check for existance
		$temp = self::$database->get(self::$table, ['user_id'], [
			'OR'=>[
				'username'=>$data['username'],
				'email[~]'=>';'.$data['email']
			]
		]);

		if($temp !== false){
			if($temp['username'] === $data['username'])
				return [false, 'Username already used'];
			return [false, 'Email already used'];
		}

		// Add email separator for preserving multiple email
		$data['email'] = ';'.$data['email'];

		// Hash password
		$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost'=>10]);
		if(!$data['password']) trigger_error("Failed to hash password");

		$userID = self::$database->insert(self::$table, $data, 'user_id');
		return [true, $userID];
	}

	public static function isUsernameExist($username)
	{
		if(self::$database->get(self::$table, ['user_id'], ['username'=>$username]) !== false)
			return true;
		return false;
	}

	public static function isEmailExist($email)
	{
		if(self::$database->get(self::$table, ['user_id'], ['email[~]'=>$email]) !== false)
			return true;
		return false;
	}
}
Auth::init();