<?php
namespace Scarlets;

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

Console::command('help', function(){
	echo("\nType [command /?] to see help section if provided\n\n");
	$list = Console::collection();
	$table = [["Command List", "Description"]];
	foreach ($list as $key => $value) {
		$table[] = [$key, $value];
	}
	Console::table($table);
}, 'Show registered command list');

Console::command(['serve {0} {1} {2}', 'serve {0} {1}', 'serve {0}', 'serve'], function($port=8000, $address=0, $options=0){
	include_once \Scarlets::$registry['path.framework.library']."/Server/Server.php";

	if($address === 'network')
		$address = gethostbyname(gethostname());
	Library\Server\start(is_numeric($port) ? $port : 8000, $address, $options);
}, 'Serve your app from your computer');

Console::help('serve', 
	"serve [port] [address] [options]
 - Address: localhost, network, IP Address
 - Available options: no-logs, silent"
);