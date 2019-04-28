<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This is where you can register console command. You can also use 
| 'help' command to view all command list
|
*/
use \Scarlets\Console;
// use \App\Console\Controllers\YourController;

// Zip any changed files
Console::command('publish', function(){
	if(file_exists(__DIR__.'/cli_publish.php'))
		include __DIR__.'/cli_publish.php';
	else return "Command not available";
});

Console::command('countdown {0} {1}', function($start, $end){
	while ($start >= $end) {
		echo("\rCounting down $start");
		$start--;
		sleep(1);
	}
	echo("\rCount down finished!");
});

// {*} will match every words after that
Console::command('echo alex {*}', function($message){

	// If you're using windows, please use 'cmder'
	// instead of windows's 'cmd' to see the chalk color
	echo Console::chalk("Alex.A> $message", 'yellow');
});

// You can also use Console::args for processing arguments
Console::command('echo {*}', function($all){

	// Split the arguments again
	Console::args("{0} {1}", function($name, $text){
		echo "$name> $text";
	});

	// Check if args above was not being called
	if(!Console::$found)
		echo "Computer> $all";
});

Console::help('echo', 
	"Looks like you're missing something.
	\rYou need to type 'echo (anything here)' to get echo back."
);

Console::command('input', function(){
	echo("Type something invisible: ");
	print_r("Result: ".Console::hiddenInput());
}, 'Type something');