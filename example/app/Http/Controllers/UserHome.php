<?php
	namespace App\Http\Controllers;

	class UserHome extends Scarlets\Auth {
		public static function route($url = ''){

			// Serve user
			if(parent::$userID){
				// Serve his homepage
				Serve::view('userhome', [
					'name' => parent::$name
				]);
			}

			// Serve guest
			else {
				// Redirect to login page or serve something
				Serve::route('login');
			}
		}
	}