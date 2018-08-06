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

Console::command('countdown {0} {1}', function($start, $end = 0){
	while ($start >= $end) {
		echo("\rCounting down $start");
		$start--;
		sleep(1);
	}
	echo("\rFinish!");
});

// {*} will match every words after that
Console::command('echo {*}', function($message){
	$temp = "Computer> ".$message;
	echo $temp;
});

// You can also use ('echo {1} {0} {*}', function($0, $1, $message))
Console::command('echo alex {*}', function($message){
	$temp = "Alex> ".$message;
	echo $temp;
});

Console::help('echo', function(){
	echo("Looks like you're missing something.
You need to type 'echo (anything here)' to get echo back.");
});

Console::command('exit', function(){
	return true;
});

Console::command('cls', function(){
	Console::clear();
});

Console::command('list', function(){
	var_dump(Console::$commands);
});