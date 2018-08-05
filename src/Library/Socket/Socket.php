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
	    if(!($input = socket_read($socket, 2048))) continue;

	    $body = '';
	    // Process header
	        $headers = explode("\r\n", $input);
	        $headers[0] = explode(" ", $headers[0]);
	        $headers['METHOD'] = $headers[0][0];
	        $headers['URL'] = $headers[0][1];
	        unset($headers[0]);
	        $zeroLength = false;

	        // Dont use foreach because headers will be replaced
	        for($i=1; $i < count($headers); $i++){
	            if($zeroLength)
	            	$body .= $headers[$i];
	            // Check if it's not zero length
	            elseif($headers[$i] !== ''){
	                $headers[$i] = explode(':', $headers[$i], 2);
	                $headers[$headers[$i][0]] = $headers[$i][1];
	            }
	            // Received zero length (header and body separator)
	            else $zeroLength = true;
	            unset($headers[$i]);
	        }

	    $func($socket, $headers, $body);

//	    $output = "HTTP/1.1 200 OK
//Content-Type: text/html\r\n\r\n [OUTPUT HERE]";

	    socket_close($socket);
	}
}