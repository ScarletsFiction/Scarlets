<?php 

namespace Scarlets\Config;

/*
---------------------------------------------------------------------------
| Scarlets Config
---------------------------------------------------------------------------
|
| Any configuration can be accessed here
|
*/

/*
	> App Configuration Path
	When you have a different config folder for your application
	you must change this path, so Scarlets Framework know
	where to load the configurations.

	(where) absolute path of the configuration
*/
function Path($where){
	if(!isset(\Scarlets::$registry['config']))
		\Scarlets::$registry['config'] = [];
	
	if(file_exists($where)){
		$list = ['app.php', 'auth.php', 'cache.php', 'database.php', 'filesystem.php', 'mail.php', 'session.php'];
		foreach($list as $value){
			\Scarlets::$registry['config'][str_replace('.php', '', $value)] = include $where.'/'.$value;
		}
		return true;
	}
	return false;
}