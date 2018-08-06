<?php

namespace Scarlets\Library\Server;
use \Scarlets;

if(!class_exists('\\Scarlets\\Library\\Socket'))
	include_once Scarlets::$registry['path.framework.library']."/Socket/Socket.php";

function start($port=80, $address='localhost'){
	// Use this console as web server
	Scarlets::Website();

	// Disable instant output mode as the content it will be sended through socket
	Scarlets\Config::set('app', 'instant', false);

	// Remove console mode so error handler can show the website address
	Scarlets::$isConsole = false;
	$_SERVER['SERVER_NAME'] = &$address;

	// Create the socket server
	Scarlets\Library\Socket\create(function($socket, $data){
		global $publicFolder;
	    $body = '';

	    // Process header
	    $headers = explode("\r\n", $data);
	    $headers[0] = explode(" ", $headers[0]);
	    $headers['METHOD'] = $headers[0][0];
	    $headers['URI'] = $headers[0][1];

	    // Check if it requested a file
		$path = Scarlets::$registry['path.public'].$headers['URI'];
	    $file = @fopen($path, "rb");
		if($file){
		    // Get requested content type
		    $contentType = explode('/', explode(',', explode("Accept: ", $data)[1])[0])[0];
		    $contentType .= '/'.pathinfo($path)['extension'];

			$fileSize  = filesize($path);
			socket_write($socket, "HTTP/1.1 200 OK");
			socket_write($socket, "\nContent-Type: ".$contentType);
			socket_write($socket, "\nServer: Scarlets Mini Server");
			socket_write($socket, "\nContent-Length: $fileSize\n\n");
			
			fseek($file, 0);
			while(!feof($file)){
				socket_write($socket, @fread($file, 512));
				flush();
			}
			@fclose($file);
	    	return;
	    }

	    unset($headers[0]);
	    $zeroLength = false;

	    // Dont use foreach because headers will be replaced
	    for($i=1; $i < count($headers); $i++){
	        if($zeroLength)
	        	$body .= $headers[$i];

	        // Check if it's not zero length
	        elseif($headers[$i] !== ''){
	            $headers[$i] = explode(':', $headers[$i], 2);

		        // Check if there are form boundary
		        if($headers[$i][0] === 'Content-Type' && strpos($headers[$i][1], 'boundary') !== false){
		        	$body .= $headers[$i][0].':'.$headers[$i][1];
		        } elseif($headers[$i][0] === 'Cookie') {
		        	$_COOKIE = parseCookie($headers[$i][1]);
		        }

	            $headers[$headers[$i][0]] = $headers[$i][1];
	        }

	        // Received zero length (header and body separator)
	        else $zeroLength = true;
	        unset($headers[$i]);
	    }

	    // Output request to the console
	    print_r("$headers[METHOD]> $headers[URI]\n");
	    print_r($headers['User-Agent']."\n");
	    return request($socket, $headers, $body);
	}, $address, $port);
}

function request(&$socket, &$headers, &$body){
	ob_start(); // Get all process output

	// Put some information to server variable
	$_SERVER['REQUEST_METHOD'] = &$headers['METHOD'];

	// Parse GET data
	if(strpos($headers['URI'], '?') !== false){
		$headers['URI'] = explode('?', $headers['URI']);
		$_SERVER['GET'] = mb_parse_str($headers['URI'][1]);
		$_SERVER['REQUEST_URI'] = $headers['URI'][0];
	} else {
		$_SERVER['GET'] = [];
		$_SERVER['REQUEST_URI'] = &$headers['URI'];
	}

	// Parse POST and FILES data
	if($headers['METHOD'] === 'POST'){
		$data = parsePostData($body);
		$_SERVER['POST'] = &$data['post'];
		$_FILES = &$data['file'];
	}

	// Find the matched router
	$found = false;
	$router = &Scarlets::$registry['Route'][$headers['METHOD']];
	foreach ($router as $key => $func) {
		if($key === $headers['URI']){ // ToDo: implement regex
			$func($body);
			$found = true;
		}
	}

	$output = "HTTP/1.1 200 OK\nServer: Scarlets Mini Server\nContent-Type: text/html\r\n\r\n";
	// Check if there are any error
	if(!Scarlets\Error::$hasError){
		socket_write($socket, $output.ob_get_contents());
	} else {
		Scarlets\Error::$hasError = false;
		socket_write($socket, $output.'There are some error');
	}

	// Clear memory
	ob_end_clean();
}

function &parsePostData(&$postRaw)
{
	// Parse JSON
	$firstChar = substr($postRaw, 0, 1);
	if($firstChar === '{' || $firstChar === '[')
		return json_decode($postRaw, true);

    // Find post boundary
    preg_match('/boundary=(.*)\r/', $postRaw, $matches);
    $boundary = str_replace(["\r", "\n"], '', $matches[1]);
    $data = [
        'post'=>[],
        'file'=>[]
    ];

    // Check if simple query
    if (!strlen($boundary)) {
        parse_str(urldecode($postRaw), $result);
        $data['post'] = $result;
        return $data;
    }

    else {
        $array = preg_split("/-+$boundary/", $postRaw);
        array_shift($array); // Http header
        array_pop($array); // End of boundary

        foreach($array as $key => $value) {
            if(empty($value))
                continue;

            // Parse stream
            if(strpos($value, 'application/octet-stream') !== FALSE){
                preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $value, $match);
                $data['post'][$match[1]] = !empty($match[2]) ? $match[2] : '';
                continue;
            }

            // Parse received file
            else if(strpos($value, 'filename') !== FALSE) {
                preg_match('/name=\"([^\"]*)\"; filename=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $value, $match);
                preg_match('/Content-Type: (.*)?/', $match[3], $mime);

                $image = preg_replace('/Content-Type: (.*)[^\n\r]/', '', $match[3]);
                $path = sys_get_temp_dir().'/php'.substr(sha1(rand()), 0, 6);
                $err = file_put_contents($path, ltrim($image));

                if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
                    $index = $tmp[1];
                } else {
                    $index = $match[1];
                }

                $data['file'][$index]['name'] = $match[2];
                $data['file'][$index]['type'] = $mime[1];
                $data['file'][$index]['tmp_name'] = $path;
                $data['file'][$index]['error'] = ($err === FALSE) ? $err : 0;
                $data['file'][$index]['size'] = filesize($path);

                continue;
            }

            // Parse multiform data
            else {
	            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $value, $match);

	            if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp))
	                $data['post'][$tmp[1]][] = (!empty($match[2]) ? $match[2] : '');
	            else
	                $data['post'][$match[1]] = (!empty($match[2]) ? $match[2] : '');
            }
        }
    }

    return $data;
}

function &parseCookie(&$cookieRaw){
	$data = explode('; ', $cookieRaw);
	$result = [];
	foreach ($data as $value) {
        $value = explode('=', $value);
        $result[$value[0]] = $value[1];
	}
	return $result;
}

function serveFile($path, $fileName = null, $bufferCallback = false){
	$file_path  = $path;

	if(is_file($file_path))
	{
		$filetime = filemtime($file_path);
		$time = date('r', $filetime);

		session_cache_limiter('none');
		header("Pragma: public");
		header("Cache-Control: public");
		header("Last-Modified: $time");

		if(@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $filetime){
		    header('HTTP/1.0 304 Not Modified');
			header('Content-Length: 0');
		    exit;
		}

		set_time_limit(0);
		$path_parts = pathinfo($file_path);
		$file_name  = $fileName?$fileName:$path_parts['basename'];
		$file_size  = filesize($file_path);

		if($fileName){
			$file_ext = explode('.', $fileName);
			$file_ext = $file_ext[count($file_ext)-1];
		}
		else{
			$file_ext = $path_parts['extension'];
		}

		$streamable = str_replace(['swf', 'pdf', 'gif', 'png', 'jpeg', 'jpg', 'ra', 'ram', 'ogg', 'wav', 'wmv', 'avi', 'asf', 'divx', 'mp3', 'mp4', 'mpeg', 'mpg', 'mpe', 'mov', 'swf', '3gp', 'm4a', 'aac', 'm3u'], '*found#@', $file_ext);
		$streamable = $streamable=='*found#@';
	    $ctype = fileTypes($file_ext);

		if($streamable){
			header('Content-Disposition: inline;');
		    header('Content-Transfer-Encoding: binary');
		}
		else{
		    header("Content-Disposition: attachment; filename=\"$file_name\"");
		}
	    header("Content-Type: " . $ctype);
		header('Accept-Ranges: bytes');

		if(isset($_SERVER['HTTP_RANGE'])){
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			if($size_unit == 'bytes'){
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
			}
			else{
				$range = '';
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				exit;
			}

			list($seek_start, $seek_end) = explode('-', $range, 2);
			$seek_end   = min(abs(intval($seek_end)),($file_size - 1));
			$seek_start = ($seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

			header('HTTP/1.1 206 Partial Content');
			header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
			header('Content-Length: '.($seek_end - $seek_start + 1));
		}
		else{
			header("Content-Length: $file_size");
			readfile($file_path);
			exit;
		}

		$file = @fopen($file_path,"rb");
		if($file){
			fseek($file, $seek_start);

			while(!feof($file)) 
			{
				if($bufferCallback !== false){
					$bufferCallback(@fread($file, 512));
				}
				else print(@fread($file, 512));
				ob_flush();
				flush();
				if($bufferCallback === false && connection_status() != 0) 
					break;
			}
			@fclose($file);
		}
	}
	header("HTTP/1.0 500 Internal Server Error");
	exit;
}