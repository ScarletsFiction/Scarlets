<?php 

namespace Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Config
---------------------------------------------------------------------------
|
| Any configuration can be accessed here
|
*/

class Config{

	/*
		> App Configuration Path
		When you have a different config folder for your application
		you must change this path, so Scarlets Framework know
		where to load the configurations.

		(where) absolute path of the configuration
	*/
	public static function path($where){
		if(!isset(\Scarlets::$registry['config']))
			\Scarlets::$registry['config'] = [];
		
		if(file_exists($where)){
			$list = ['app.php', 'auth.php', 'cache.php', 'database.php', 'filesystem.php', 'mail.php', 'session.php'];
			$reg = &\Scarlets::$registry['config'];
			foreach($list as $value){
				$name = str_replace('.php', '', $value).'.';
				$config = include $where.'/'.$value;
				foreach($config as $key => $value){
					$reg[$name.$key] = $value;
				}
			}
			return true;
		}
		return false;
	}
}