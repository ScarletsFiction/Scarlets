<?php
namespace App\Auth;
use Scarlets\Library\Database;
use Scarlets\Route\Serve;
use Scarlets\User\Auth;
use Scarlets\User\Session;
use Scarlets\User\AccessToken;

class User{
	/*
		[
			userID => false,
			username => false
		]
	*/
	public static $data = false;

	// For combining $data with other array data
	public static function combine($arrayToMerge){
		if(self::$data === false)
			self::init();

		return array_merge($arrayToMerge, self::$data);
	}

	public static function init(){
		// Check for access token
		if(AccessToken::parseAvailableToken()) {
			$userDatabase = Database::connect('user');
			$user = [
				'userID' => AccessToken::$data['userID'],
				'username' => $userDatabase->get('users', ['username'], ['user_id'=>AccessToken::$data['userID']])['username'],
			];
		}

		// Check if the access token have an error
		elseif(AccessToken::$error)
			Serve::end('{"error":"Access Token was '.AccessToken::$error.'"}', 401);

		// Check for session login
		else {
			$user = Auth::getLoginData();

			if(!$user || !isset($user['userID'])) $user = [
				'userID' => false,
				'username' => false
			];

			AccessToken::$data['permissions'] = '|*|';
			AccessToken::$data['expiration'] = 'never';
		}

		self::$data = [
			'userID' => $user['userID'],
			'username' => $user['username'],
		];
	}
}