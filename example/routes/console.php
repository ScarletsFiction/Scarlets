<?php

use \Scarlets\Console;
/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This is where you can register console command.
|
*/

Console::command('display {0} {1}', function($message, $optional = null){
	$temp = "I got: ".$message;

	if($optional !== null)
		$temp .= ' - '.$optional;

	echo $temp;
}, '1 argument are required');
