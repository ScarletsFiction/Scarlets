<?php
use Scarlets\User\Auth;
namespace App\Http\Controllers\User;

class Home {
	public static $user = [];

	public static function route($url = ''){
		self::$user = Auth::getLoginData();

		// Serve user
		if(self::$user){
			// Serve his homepage
			Serve::view('userhome', [
				'name' => self::$user['username']
			]);
		}

		// Serve guest
		else {
			// Redirect to login page or serve something
			Serve::route('login');
		}
	}
}