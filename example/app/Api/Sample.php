<?php
namespace App\Api;
use Scarlets\Library\Database;

class Sample{
	// This is just a sample API
	public static function website(){

		// Return data to the current middleware
		return [
			'active'=>true,
			'random'=>floor(rand() * 100)
		];
	}
}