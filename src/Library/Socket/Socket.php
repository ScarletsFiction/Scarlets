<?php

namespace Scarlets\Library\Socket;

/*
	> Create socket server

	(address) ..
	(port) ..
	(readCallback) ..
	(connectionCallback) ..
*/
function create($address, $port, $readCallback, $connectionCallback=0){
    set_time_limit(0);
    ob_implicit_flush();

    $address = $address ?: 'localhost';

    $sock = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_bind($sock, $address, $port) or die('Could not bind to address');
    socket_listen($sock);
    $clients = [$sock];

    $write = $except = NULL; // We doesn't use this
    $second = 1;
    $garbageWaiting = 0; // Max to 100

    // Avoid too many function call in loop
    while(1){
        usleep(10); // Avoid CPU Burn
        $read = $clients; // $read client that have data
        
        // Check if there are some data from client that can be read
        if(socket_select($read, $write, $except, $second) === 0)
            continue;

        // Check for new client connection
        if(in_array($sock, $read)){ // in_array is more faster (tested on apache benchmark)

            // Add the new client to the list
            $clients[] = $newsock = socket_accept($sock);
            unset($read[array_search($sock, $read)]);

            if($connectionCallback !== 0)
                $connectionCallback($newsock, 'connected');
        }

        foreach($read as &$read_sock){
            $data = @socket_read($read_sock, 2048);
            $keep = true;

            // when readCallback return true,  disconnect and remove the socket
            if(trim($data) !== '' && !$readCallback($read_sock, $data))
                continue;

            // Remove disconnected client from the list
            unset($clients[array_search($read_sock, $clients)]);
            $garbageWaiting++;

            if($garbageWaiting>100)
                $clients = array_values($clients);

            if($connectionCallback !== 0)
                $connectionCallback($read_sock, 'disconnected');

            socket_close($read_sock);
            unset($read_sock);
        }
    }
    socket_close($sock);
}

/*
	> Simple socket handler

	(readCallback) ..
	(address) ..
	(port) ..
*/
function simple($address, $port, $readCallback){
	set_time_limit(0);
	ob_implicit_flush();

	$address = $address ?: 'localhost';

	$sock = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_bind($sock, $address, $port) or die('Could not bind to address');
	socket_listen($sock);

	// Avoid too many function call in loop
	while(1){
	    $socket = socket_accept($sock);
	    $data = @socket_read($socket, 2048);
	    if($data === false){
	    	socket_close($socket);
	    	continue;
	    }

	    $readCallback($socket, $data);
	    socket_close($socket);
	}
}