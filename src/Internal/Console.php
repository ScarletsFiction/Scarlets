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
});

Console::command('cls', function(){
	Console::clear();
});

Console::command(['serve {0} {1}', 'serve {0}', 'serve'], function($port=8000, $address=0){
	include_once \Scarlets::$registry['path.framework.library']."/Server/Server.php";
	if($port === 'network')
		Library\Server\start(80, gethostbyname(gethostname()));
	else
		Library\Server\start(is_numeric($port) ? $port : 8000, $address);
});