<?php

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

    return $temp;
}, '1 argument are required');
