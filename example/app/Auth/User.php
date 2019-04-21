<?php
namespace App\Auth;
use Scarlets\Route\Serve;
use Scarlets\User\Auth;
use Scarlets\User\Session;
use Scarlets\User\AccessToken;
use App\DB;

class User{
	public static $id = false;
	public static $username = false;

	# For combining $data with other array data
	public static function &combine($from){
		if(self::$data === false)
			self::init();

		$from['userID'] = &self::$id;
		$from['username'] = &self::$username;

		return $from;
	}

	public static function init(){
		# Check for access token
		if(AccessToken::parseAvailableToken()) {
			# You must configure your database on middleware `App\Middleware`
			$user = [
				'userID' => AccessToken::$userID,
				'username' => DB::$myDB->get('users', ['username'], ['user_id'=>AccessToken::$userID])['username'],
			];
		}

		# Check if the access token have an error
		elseif(AccessToken::$error)
			Serve::end('{"error":"Access Token was '.AccessToken::$error.'"}', 401);

		# Check if logged in and  have user session
		else {
			$user = Auth::getLoginData();

			if(!$user || !isset($user['userID'])) $user = [
				'userID' => false,
				'username' => false
			];

			AccessToken::$data['permissions'] = '|*|';
			AccessToken::$data['expiration'] = 'never';
		}

		self::$id = $user['userID'];
		self::$username = $user['username'];
	}
}
User::init();