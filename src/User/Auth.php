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

		// If the table was not found
		self::$database->onTableMissing(self::$table, function(){
			self::$database->createTable(self::$table, [
				'user_id' => ['bigint(19)', 'primary', 'key', 'AUTO_INCREMENT'],
				'username' => ['text', 'COLLATE', 'latin1_bin'],
				'password' => ['text', 'COLLATE', 'latin1_bin'],
				'email' => ['text', 'COLLATE', 'latin1_bin'],
				'failed_login' => ['tinyint(10)', 'default', 0],
				'login_time' => ['int(11)', 'default', 0],
				'last_created' => ['int(11)', 'default', 0]
			]);
		});
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
		Session::saveSify();
	}

	// $data = [username=>'', password=>'']
	// $where = ['blocked' => false]
	// $beforeVerify, $onSuccess = function($row){}
	// if beforeVerify return true, then the login will failed
	// Return true if success, false if the login was failed, or a message if something invalid
	public static function login($data, $where = false, $beforeVerify = false, $onSuccess = false){
		$username = strtolower($data['username']);
		$column = ['user_id', 'password', 'username'];
		$where_ = [];

		// Email
		if(strpos($username, '@') > 3){
			if(filter_var($username, FILTER_VALIDATE_EMAIL))
				return 'Email not valid';

			$where_ = ['email[,]'=>$username];
		}

		// Username
		else{
			if(preg_match('/[^a-zA-Z0-9]/', $username))
				return 'Username not valid';

			$where_ = ['username'=>$username];
		}

		// Add extra where condition
		if($where)
			$where_ = array_merge($where_, $where);

		// Get data from database
		$row = self::$database->get(self::$table, $column, $where_);
		if($row === false)
			return 'Username not found';

		$passwordHash = &$row['password'];
		unset($row['password']);

		// Call $beforeVerify (Useful for checking if user login was blocked)
		if(is_callable($beforeVerify)){
			$status = $beforeVerify($row);
			if($status) return $status;
		}

		// Verify user password then save it to user sessions
		if(password_verify($data['password'], $passwordHash)){
			// Call $onSuccess (Useful for update login time, counter, add sifyData before saved, or revoke current login state)
			// if onSuccess return true, the login data will not saved but this function will still return true
			if(is_callable($onSuccess) && $onSuccess($row))
				return true;

			Session::$data['username'] = Session::$sify['username'] = &$row['username'];
			Session::$data['userID'] = Session::$sify['userID'] = (int)$row['user_id'];
			Session::saveSify();
			return true;
		}
		return false;
	}

	// Return {userID, username} if logged in
	public static function getLoginData($returnBool = false){
		$sify = &Session::$sify;
		$data = &Session::$data;

		// Check if cookie and session data have userID
		if(isset($data['userID']) && isset($sify['userID']))
		{
			// Check if exist but different userID
			if($data['userID'] && $sify['userID'] && $data['userID'] !== $sify['userID']){
				Session::destroy();
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
	// return intval(user_id)
	// return 'error message'
	public static function register($data){
		// Validate email
		if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
			return 'Email not valid';

		$emailDomain = explode('@', $data['email'])[1];

		getmxrr($emailDomain, $mx);
		if(empty($mx))
			return 'Email not valid';

		// Validate username
		if(strlen(preg_replace('/[^a-zA-Z0-9]/', '', $data['username'])) < strlen($data['username']))
			return 'Username not valid';

		// Check for existance
		$temp = self::$database->get(self::$table, ['user_id', 'username'], [
			'OR'=>[
				'username'=>$data['username'],
				'email[,]'=>$data['email']
			]
		]);

		if($temp !== false){
			if($temp['username'] === $data['username'])
				return 'Username already used';
			return 'Email already used';
		}

		$data['email'] = ",$data[email],";
		$data['username'] = strtolower($data['username']);

		// Hash password
		$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost'=>10]);
		if(!$data['password']) throw new Exception('Failed to hash password');

		$userID = self::$database->insert(self::$table, $data, true);
		return $userID+0;
	}

	// Add email into user_id/username
	// return true if success
	// return 'error message'
	public static function addEmail($userID, $email){
		// Validate email
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			return 'Email not valid';

		if(self::$database->type === 'SQL' && self::$database->has(self::$table, ['email[,]'=>$email]))
			return 'Email already used';

		self::$database->update(self::$table, ['email[,]'=>$email], ['user_id'=>$userID]);
		return true;
	}
}
Auth::init();