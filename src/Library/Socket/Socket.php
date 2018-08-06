<?php

namespace Scarlets\Library\Socket;

/*
	> Create

	(callback) ..
	(address) ..
	(port) ..
*/
function create($callback, $address=0, $port=80){
	set_time_limit(0);
	ob_implicit_flush();

	$address = $address ?: 'localhost';
	$port = $port;

	$sock = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_bind($sock, $address, $port) or die('Could not bind to address');

	echo "\n Listening On port $port For Connection... \n\n";
	socket_listen($sock);

	// Avoid too many function call in loop
	while(1){
	    $socket = socket_accept($sock);
	    if(!($input = socket_read($socket, 2048))){
	    	socket_close($socket);
	    	continue;
	    }

	    $callback($socket, $input);
	    socket_close($socket);
	}
}