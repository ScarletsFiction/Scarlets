<?php
namespace App\Http\Controllers\User;
use \Scarlets\Route\Serve;

class Home {
	public static $fallback = 'Please input username';

	public static function route($username){
		if(!$username)
			return Serve::raw(self::$fallback);

		Serve::raw("Your username: $username");
	}
}