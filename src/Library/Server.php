<?php
namespace Scarlets\Library;
use \Scarlets;

class Server {
	public static $pendingHeader = '';
	public static $requestMicrotime = 0;
	private static $whenComplete = [];
	private static $whenStart = [];

	/*
		> Listen to request finish event
		Here you can register a event handler after
		any client request was finished 

		(function) function
	*/
	public static function onComplete($function){
		if(array_search($function, self::$whenComplete) === false)
			self::$whenComplete[] = $function;
	}

	/*
		> Listen to request start event
		Here you can register a event handler before
		any client request was started

		(function) function
	*/
	public static function onStart($function){
		if(array_search($function, self::$whenStart) === false)
			self::$whenStart[] = $function;
	}

	/*
		> Set header sent from the server

		(text) string
	*/
	public static function setHeader($text){
		self::$pendingHeader .= "\n".$text;
	}

	public static function setCookie($name, $value, $time=3600, $path='/', $domain=false, $secure=false, $http_only=false){
		// Domain check
		$domain = explode('.', $domain);
		if(count($domain) === 2 && $domain[0] === '')
			unset($domain[0]);

		$domain = explode(':', implode('.', $domain))[0];

    	$date = date('D, d M Y H:i:s', $time) . ' GMT';
    	$cookie = "Set-Cookie: $name=".rawurlencode($value)."; Expires=$date; Max-Age=".($time - time())."; Path=$path";
    	if($domain)
    		$cookie .= "; Domain=$domain";
    	if($secure)
    		$cookie .= '; Secure';
    	if($http_only)
    		$cookie .= '; HttpOnly';
		self::setHeader($cookie);
	}

	public static function start($port, $address, $options){
		// Use this console as web server
		Scarlets::Website();

		// Disable instant output mode as the content it will be sended through socket
		Scarlets\Config::set('app', 'instant', false);

		// Remove console mode so error handler can show the website address
		$_SERVER['SERVER_NAME'] = &$address;

	    echo Scarlets\Console::chalk("\nScarlets server started on ", 'green');
	    if($address !== 'public')
	   		echo 'http://'.$address.($port !== 80 ? ':'.$port : '');
	   	else {
	   		$IPs = Scarlets\Library\Socket::getIPAddress();

	   		if(count($IPs) > 1) echo ":\n";
	   		foreach ($IPs as $value) {
	   			echo 'http://'.$value.($port !== 80 ? ':'.$port : '');
	   		}
	   	}
	    echo "\nUse CTRL+C 2 times to exit\n\n";

		// Create the socket server
		Scarlets\Library\Socket::create($address, $port, function($socket, $data) use($options) {
			self::$requestMicrotime = microtime(true);

		    // Run any start event if exist
			$list = &self::$whenStart;
			foreach ($list as &$function)
				$function();

		    $body = '';
		    socket_getpeername($socket, $address);
		    $_SERVER['REMOTE_ADDR'] = $address;

		    // Process header
		    $headers = explode("\r\n", $data);
		    $headers[0] = explode(' ', $headers[0]);
		    $headers['METHOD'] = $headers[0][0];
		    $headers['URI'] = $headers[0][1];

			// Reroute last slash
			if(Scarlets\Config::$data['app.sensitive_web_route'] === false){
				$requestURI = explode('?', $headers['URI'], 2);
				if(substr($requestURI[0], -1, 1) === '/' && $requestURI[0] !== '/'){
					$matched = true;
					$headers['URI'] = substr($requestURI[0], 0, -1);

					if(isset($requestURI[1]))
						$headers['URI'] .= "?$requestURI[1]";
				}
			}

		    // Check if it requested a file
			$path = Scarlets::$registry['path.public'].explode('?', $headers['URI'], 2)[0];
		    $file = @fopen($path, 'rb');
			if($file){
				$info = pathinfo($path);
				if(isset($info['extension'])){
					socket_write($socket, 'HTTP/1.1 200 OK');
					
				    // Get requested content type
				    $accept = explode('Accept: ', $data);
				    if(isset($accept[1])){
					    $contentType = explode('/', explode(',', $accept[1])[0])[0];
					    $contentType .= '/'.pathinfo($path)['extension'];
						socket_write($socket, "\nContent-Type: ".$contentType);
				    }

					$fileSize  = filesize($path);
		  			$time = date('r', filemtime($path));
					socket_write($socket, "\nServer: Scarlets Mini Server");
					socket_write($socket, "\nPragma: public");
					socket_write($socket, "\nCache-Control: public");
					socket_write($socket, "\nLast-Modified: $time");
					socket_write($socket, "\nConnection: close");
					socket_write($socket, "\nContent-Length: $fileSize\n\n");

					fseek($file, 0);
					while(!feof($file)){
						socket_write($socket, @fread($file, 16384));
						flush();
					}
					@fclose($file);
			    	return true;
				}
		    }

		    unset($headers[0]);
		    $zeroLength = false;

		    // Dont use foreach because headers will be replaced
		    for($i=1; $i < count($headers); $i++){
		        if($zeroLength)
		        	$body .= $headers[$i];

		        // Check if it's not zero length
		        elseif($headers[$i] !== ''){
		            $headers[$i] = explode(': ', $headers[$i], 2);

			        // Check if there are form boundary
			        if($headers[$i][0] === 'Content-Type' && strpos($headers[$i][1], 'boundary') !== false)
			        	$body .= $headers[$i][0].': '.$headers[$i][1];
			        elseif($headers[$i][0] === 'Cookie')
			        	$_COOKIE = self::parseCookie($headers[$i][1]);

		            $headers[$headers[$i][0]] = $headers[$i][1];
		        }

		        // Received zero length (header and body separator)
		        else $zeroLength = true;
		        unset($headers[$i]);
		    }

		    // Output request to the console
		    if($options & 1)
		    	print_r("$_SERVER[REMOTE_ADDR] ($headers[METHOD])> $headers[URI]");

		    $httpstatuscode = self::request($socket, $headers, $body);

		    if($options & 1){
				print_r(" [$httpstatuscode]\n");
				print_r($headers['User-Agent']."\n");
		    }

		    // Run any complete event if exist
			$list = &self::$whenComplete;
			foreach ($list as &$function)
				$function();

		    return true;
		});
	}

	public static function &request(&$socket, &$headers, &$body){
		$httpstatuscode = 0;

		ob_start(); // Get all process output

		// Put some information to server variable
		$_SERVER['REQUEST_METHOD'] = &$headers['METHOD'];

		// Parse GET data
		if(strpos($headers['URI'], '?') !== false){
			$headers['URI'] = explode('?', $headers['URI']);
			mb_parse_str($headers['URI'][1], $_GET);
			$_SERVER['REQUEST_URI'] = &$headers['URI'][0];
		} else {
			$_GET = [];
			$_SERVER['REQUEST_URI'] = &$headers['URI'];
		}

		// Parse POST and FILES data
		if($headers['METHOD'] === 'POST'){
			$data = self::parsePostData($body);
			$_POST = &$data['post'];
			$_FILES = &$data['file'];
		}

		$_REQUEST = array_merge($_POST, $_GET);

		// Put some data to SERVER variable
		if(isset($headers['User-Agent']))
			$_SERVER['HTTP_USER_AGENT'] = &$headers['User-Agent'];
		if(isset($headers['Referer']))
			$_SERVER['HTTP_REFERER'] = &$headers['Referer'];
		if(isset($headers['Host']))
			$_SERVER['HTTP_HOST'] = &$headers['Host'];
		if(isset($headers['Accept']))
			$_SERVER['HTTP_ACCEPT'] = &$headers['Accept'];
		if(isset($headers['Accept-Language']))
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = &$headers['Accept-Language'];
		if(isset($headers['Origin']))
			$_SERVER['HTTP_ORIGIN'] = &$headers['Origin'];
		// $headers['Accept-Encoding'];

		// Find the matched router
		$found = false;
		$router = &Scarlets::$registry['Route'][$headers['METHOD']];
		foreach ($router as $key => $func) {
			if(\Scarlets\Route::handleURL($key, $func[0], $func[1]))
				$found = true;
		}

		if(!$found){
			$router = &Scarlets::$registry['Route']['ANY'];
			foreach ($router as $key => $func) {
				if(\Scarlets\Route::handleURL($key, $func[0], $func[1]))
					$found = true;
			}
		}

		$output = self::$pendingHeader;
		self::$pendingHeader = '';
		$output .= "\nServer: Scarlets\nConnection: close\nContent-Type: text/html\r\n\r\n";

		if(!$found){
			ob_get_contents();
			$router = &Scarlets::$registry['Route']['STATUS'];

			// Check for local file
			$path = Scarlets::$registry['path.public'].$headers['URI'];
			if(is_dir($path)){
				if(substr($headers['URI'], -1) === '/') {
					if(is_file($path.'index.php')){
						$output = "HTTP/1.1 200 OK".$output;
						try{
							include $path.'index.php';
							socket_write($socket, $output.ob_get_contents());
							$httpstatuscode = 200;
						}catch(\Exception $e){
							Scarlets\Error::checkUncaughtError();
						}
					}
					else if(is_file($path.'index.html'))
						include $path.'index.html';
				} else {
					socket_write($socket, 'HTTP/1.1 302 Moved Temporary');
					socket_write($socket, "\nLocation: $headers[URI]/".$output);
					$httpstatuscode = 302;
				}
			}

			// Check if there are 404 http handler
			elseif(isset($router['404'])){
				$output = "HTTP/1.1 404 Not Found$output";
				try{
					$router['404']();
					socket_write($socket, $output.ob_get_contents());
					$httpstatuscode = 404;
				}catch(\Exception $e){
					Scarlets\Error::checkUncaughtError();
				}
			}
		}

		// Make sure there are no error before output
		if(!Scarlets\Error::$hasError){
			if($httpstatuscode === 0){
				$output = "HTTP/1.1 200 OK$output";
				socket_write($socket, $output.ob_get_contents());
				$httpstatuscode = 200;
			}
		} else {
			$router = &Scarlets::$registry['Route']['STATUS'];
			if(isset($router['500'])){
				if(ob_get_contents()) ob_end_clean();
				$output = "HTTP/1.1 500 Internal Server Error$output";
				$httpstatuscode = 500;
				try{
					$router['500']();
					socket_write($socket, $output.ob_get_contents());
				}catch(\Exception $e){
					Scarlets\Error::checkUncaughtError();
				}
			}
		}

		// Clear memory
		if(ob_get_contents()) ob_end_clean();
		self::$pendingHeader = '';

		// Output error to console
		if(Scarlets\Error::$hasError)
			print_r(Scarlets\Error::getUnreadError());
		return $httpstatuscode;
	}

	public static function &parsePostData(&$postRaw)
	{
	    $data = [
	        'post'=>[],
	        'file'=>[]
	    ];

		// Parse JSON
		$firstChar = substr($postRaw, 0, 1);
		if($firstChar === '{' || $firstChar === '['){
			$data['post'] = json_decode($postRaw, true);
			return $data;
		}

	    // Find post boundary
	    preg_match('/boundary=(.*)\r/', $postRaw, $matches);
	    if(!$matches){
			mb_parse_str($postRaw, $temp);
			$data['post'] = $temp;
			return $data;
	    }
	    
	    $boundary = trim($matches[1]);

	    // Check if simple query
	    if (!strlen($boundary)) {
	        parse_str(urldecode($postRaw), $result);
	        $data['post'] = $result;
	        return $data;
	    }

	    $data = Scarlets\Route\Parser::formDataRequest($boundary, $postRaw);
	    return $data;
	}

	public static function &parseCookie(&$cookieRaw){
		$data = explode('; ', $cookieRaw);
		$result = [];
		foreach ($data as $value) {
	        $value = explode('=', $value);
	        $result[$value[0]] = $value[1];
		}
		return $result;
	}

	public static function serveFile($path, $fileName = null, $bufferCallback = false){
		$file_path  = $path;

		if(is_file($file_path))
		{
			$filetime = filemtime($file_path);
			$time = date('r', $filetime);

			session_cache_limiter('none');
			header('Pragma: public');
			header('Cache-Control: public');
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
		    header('Content-Type: ' . $ctype);
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
				header("Content-Range: bytes $seek_start-$seek_end/$file_size");
				header('Content-Length: '.($seek_end - $seek_start + 1));
			}
			else{
				header("Content-Length: $file_size");
				readfile($file_path);
				exit;
			}

			$file = @fopen($file_path, 'rb');
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
		header('HTTP/1.0 500 Internal Server Error');
		exit;
	}
}