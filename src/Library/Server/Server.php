<?php

namespace Scarlets\Library\Server;

if(!class_exists('\\Scarlets\\Library\\Socket'))
	include_once \Scarlets::$registry['path.framework.library']."/Socket/Socket.php";
function start($port=80, $address=0){
	\Scarlets\Library\Socket\create(function($socket, $data){
	    $body = '';
	    // Process header
	        $headers = explode("\r\n", $data);
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

	    print_r($headers, $body);

//	    $output = "HTTP/1.1 200 OK
//Content-Type: text/html\r\n\r\n [OUTPUT HERE]";
	}, $address, $port);
}