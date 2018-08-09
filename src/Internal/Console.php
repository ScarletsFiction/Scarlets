<?php
namespace Scarlets;
use \Scarlets;

/*
---------------------------------------------------------------------------
| Register internal function
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

Console::command('exit', function(){
	return true;
}, 'Exit this console (CTRL+Z and enter)');

Console::command('cls', function(){
	Console::clear();
}, 'Clear console');

Console::command(['upgrade {0}', 'upgrade'], function($options=0){
	include "Upgrade.php";
}, 'Clear console');

Console::command('help', function(){
	echo("\nType [command /?] to see help section if provided\n\n");
	$list = Console::collection();
	$table = [["Command List", "Description"]];
	foreach ($list as $key => $value) {
		$table[] = [$key, $value];
	}
	Console::table($table);
}, 'Show registered command list');

Console::command(['serve {0} {1} {*}', 'serve {0} {1}', 'serve {0}', 'serve'], function($port=8000, $address='localhost', $options=0){
	if($address === 'network')
		$address = gethostbyname(gethostname());

	if($options !== 0){
		$temp = explode(' ', $options);
		$options = 0;
		if(in_array('--verbose', $temp)) $options |= 1;
		if(in_array('--log', $temp)) $options |= 2;
	}
	Scarlets\Library\Server::start(is_numeric($port) ? $port : 8000, $address, $options);
}, 'Serve your app from your computer');

Console::help('serve', 
	"serve [port] [address] [options]
 - Address: localhost, network, IP Address
 - Available options: no-logs, silent"
);