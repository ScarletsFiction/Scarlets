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
Console::command('echo alex {*}', function($message){
	echo "Alex.A> $message";
});

// You can also use Console::args for processing arguments
Console::command('echo {*}', function($all){

	// Split the arguments again
	Console::args("{0} {*}", function($name, $all){
		echo "$name> $all";
	});

	// Check if args above was not being called
	if(!Console::$found)
		echo "Computer> $all";
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