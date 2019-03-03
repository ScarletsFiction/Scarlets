<?php 
namespace Scarlets\Library;
use \Scarlets;

class WebRequest{
	public static function loadURL($url, $options = false){
		$userAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36';
		$headers = ['Accept'=>'*/*;q=0.8'];
		
		$ch = curl_init();
		
		if($options){
			if(isset($options['ssl'])){ # 0, 1
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options['ssl']);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['ssl']);
			}

			if(isset($options['header'])) # ['X-Custom'=>'values']
				$headers = array_merge($headers, $options['header']);

			if(isset($options['method'])){ # 'post'
				$options['method'] = strtoupper($options['method']);

				if(isset($options['data'])){
    				// Default GET parameter
					if($options['method'] === 'GET' || $options['method'] === 'HEAD')
						$url .= '?'.http_build_query($options['data']);

					elseif($options['method'] === 'POST'){
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
					}

    				// Encode the data as JSON and send on the body
					else {
						if($options['method'] === 'JSON_POST')
							curl_setopt($ch, CURLOPT_POST, 1);

						$headers['Content-Type'] = 'application/json; charset=utf-8';
						curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['data']));
					}

					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
				}

				elseif($options['method'] === 'HEAD')
					curl_setopt($ch, CURLOPT_NOBODY, 1);

    			else curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
    		}

    		// Default GET parameter
			else if(isset($options['data'])){ # ['field' => 'value']
				$url .= '?'.http_build_query($options['data']);
			}

			if(isset($options['cookiefile'])){ # 'path/to/file'
				if(!file_exists($options['cookiefile']))
					file_put_contents($options['cookiefile'], '');

				curl_setopt($ch, CURLOPT_COOKIE, file_get_contents($options['cookiefile']));
			}

			if(isset($options['cookie'])) # 'field=value;data=values'
				curl_setopt($ch, CURLOPT_COOKIE, $options['cookie']);

			if(isset($options['limitSize'])){ # in KB
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // more progress info
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function(
					$DownloadSize, $Downloaded, $UploadSize, $Uploaded
				) use($options) {
					// If $Downloaded exceeds 1KB, returning non-0 breaks the connection!
					return ($Downloaded > ($options['limitSize'] * 1024)) ? 1 : 0;
				});
			}

			if(isset($options['proxy'])){ # [ip=>127.0.0.1, port=>3000, *userpass=>'user:pass']
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
				curl_setopt($ch, CURLOPT_PROXY, $options['proxy']['ip']);
				curl_setopt($ch, CURLOPT_PROXYPORT, $options['proxy']['port']);   

				if(isset($options['userpass']))
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['proxy']['userpass']);
			}

			if(isset($options['returnheader']) || isset($options['cookiefile']))
				curl_setopt($ch, CURLOPT_HEADER, 1);
		}

        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

		$header = [];
		foreach ($headers as $key => $value) {
			$header[] = "$key: $value";
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$result = curl_exec($ch);
		if($options){
			if(isset($options['utf8']))
				$result = mb_convert_encoding($result, 'utf-8', 'shift-jis');

			if(isset($options['returnheader']) || isset($options['cookiefile'])){
				$err = curl_errno($ch);
				$errmsg = curl_error($ch);
				$header = curl_getinfo($ch);
				curl_close($ch);

				$http_code = $header['http_code'];
				$body = substr($result, $header['header_size']);
				$header = self::parseHTTPHeader(substr($result, 0, $header['header_size']));

				if(isset($header['Set-Cookie'])){
					if(is_array($header['Set-Cookie']))
						$cookies = implode('; ', $header['Set-Cookie']);
					else $cookies = $header['Set-Cookie'];
				}
				else $cookies = false;
				
				if(isset($options['cookiefile']) && $cookies !== false){
					file_put_contents($options['cookiefile'], $cookies);

					if(!isset($options['returnheader']))
						return $body;
				}

				$return = [];
				$return['error_no'] = $err;
				$return['error_msg'] = $errmsg;
				
				// http://php.net/manual/en/function.curl-getinfo.php#41332
				$return['http_code'] = $http_code;
				$return['headers'] = $header;
				$return['body'] = $body;
				$return['cookies'] = $cookies;
				return $return;
			}
		}
		
		curl_close($ch);
		return $result;
	}

	private static function parseHTTPHeader($data){
		$data = explode("\n", str_replace("\r", '', trim($data)));
		unset($data[0]);

		$header = [];
		foreach ($data as $value) {
			$value = explode(': ', $value, 2);

			// Check if already exist
			if(isset($header[$value[0]])){
				$ref = &$header[$value[0]];
				if(is_array($ref))
					$ref[] = $value[1];
				else $ref = [$ref, $value[1]];
			}

			// Create new if not
		    elseif(isset($value[1])) $header[$value[0]] = $value[1];
		}
		return $header;
	}

	// From this server to client browser
	public static function giveFiles($filePath, $fileName = null){
		if(is_file($filePath)){
			$fileTime = filemtime($filePath);
  			$time = date('r', $fileTime);

			session_cache_limiter('none');
			header('Pragma: public');
			header('Cache-Control: public');
			header("Last-Modified: $time");

			if(@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $fileTime){
			    header('HTTP/1.0 304 Not Modified');
				header('Content-Length: 0');
			    exit;
			}

			set_time_limit(0);
			$path_parts = pathinfo($filePath);
			$file_name  = $fileName ? $fileName : $path_parts['basename'];
			$file_size  = filesize($filePath);

			if($fileName){
				$file_ext = explode('.', $fileName);
				$file_ext = $file_ext[count($file_ext)-1];
			}
			else
				$file_ext = $path_parts['extension'];

			$streamable = in_array($file_ext, ['swf', 'pdf', 'gif', 'png', 'jpeg', 'jpg', 'ra', 'ram', 'ogg', 'wav', 'wmv', 'avi', 'asf', 'divx', 'mp3', 'mp4', 'mpeg', 'mpg', 'mpe', 'mov', 'swf', '3gp', 'm4a', 'aac', 'm3u']);
		    $ctype = fileTypes($file_ext);

			if($streamable){
				header('Content-Disposition: inline;');
			    header('Content-Transfer-Encoding: binary');
			}
			else
			    header("Content-Disposition: attachment; filename=\"$file_name\"");

		    header("Content-Type: $ctype");
			header('Accept-Ranges: bytes');

			if(isset($_SERVER['HTTP_RANGE'])){
				list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
				if($size_unit == 'bytes')
					list($range, $extra_ranges) = explode(',', $range_orig, 2);
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
				readfile($filePath);
				exit;
			}
		    
			$file = @fopen($filePath, 'rb');
			if($file){
				fseek($file, $seek_start);

				while(!feof($file)) 
				{
					print(@fread($file, 512));
					ob_flush();
					flush();
					if (connection_status()!=0) 
					{
						@fclose($file);
						exit;
					}			
				}
				@fclose($file);
				exit;
			}
		}
		header('HTTP/1.0 500 Internal Server Error');
		exit;
	}
	
	// From other server to local file
	public static function download($from, $to, $type = 'default'){
		if($type === 'default'){
			$file = file_get_contents($from, false, stream_context_create([
    			'ssl'=>[
    			    'verify_peer'=>false,
    			    'verify_peer_name'=>false,
    			],
			]));
			file_put_contents($to, $file);
		}
		
		elseif($type ==='socket'){
			#chunk = 10MB
			$chunksize = 10 * (1024 * 1024);
			
			if(((string)get_http_response_code($from))!='200'){return false;}
		
			# parse_url breaks a part a URL into it's parts, i.e. host, path,
			# query string, etc.

			$parts = parse_url($from);
			$i_handle = fsockopen($parts['host'], 80, $errstr, $errcode, 5);
			$o_handle = fopen($to, 'wb');
		
			if ($i_handle == false || $o_handle == false)
				return false;
		
			if (!empty($parts['query']))
				$parts['path'] .= '?' . $parts['query'];
		
			# Send the request to the server for the file
	
			$request = "GET {$parts['path']} HTTP/1.1\r\n";
			$request .= "Host: {$parts['host']}\r\n";
			$request .= "User-Agent: Mozilla/5.0\r\n";
			$request .= "Keep-Alive: 115\r\n";
			$request .= "Connection: keep-alive\r\n\r\n";
			fwrite($i_handle, $request);
		
			# Now read the headers from the remote server. We'll need
			# to get the content length.
		
			$headers = array();
			while(!feof($i_handle)) {
				$line = fgets($i_handle);

				if ($line == "\r\n")
					break;

				$headers[] = $line;
			}
		
			# Look for the Content-Length header, and get the size
			# of the remote file.
		
			$length = 0;
			foreach($headers as $header) {
				if (stripos($header, 'Content-Length:') === 0) {
					$length = (int)str_replace('Content-Length: ', '', $header);
					break;
				}
			}
		
			# Start reading in the remote file, and writing it to the
			# local file one chunk at a time.
		
			$cnt = 0;
			while(!feof($i_handle)) {
				$buf = '';
				$buf = fread($i_handle, $chunksize);
				$bytes = fwrite($o_handle, $buf);
				if ($bytes == false)
					return false;
				$cnt += $bytes;
			
				# We're done reading when we've reached the content length
				if ($cnt >= $length) break;
			}
		
			fclose($i_handle);
			fclose($o_handle);
			return $cnt;
		}
		
		elseif($type === 'direct'){
			$rh = fopen($from, 'rb');
			$wh = fopen($to, 'wb');
			if (!$rh || !$wh)
				return false;

			while (!feof($rh)) {
				if (fwrite($wh, fread($rh, 1024)) === FALSE)
					return false;
			}

			fclose($rh);
			fclose($wh);
		}

		elseif($type === 'curl'){
			if(is_file($from))
		        copy($from, $to); 
		    else {
		        $options = array(
		        	CURLOPT_FILE    => fopen($to, 'w'),
		        	CURLOPT_TIMEOUT =>  28800, // 8 hours
		        	CURLOPT_URL     => $from
		        );

		        $ch = curl_init();
		        curl_setopt_array($ch, $options);
		        curl_exec($ch);
		        curl_close($ch);
		    }
		}

		else return false;
		return true;
	}
	
	// allowedExt = array
	// From client browser to this server
	public static function receiveFile($directory, $allowedExt, $rename = ''){
		if(!empty($_FILES)) 
		{
			$file = &$_FILES['Filedata'];
			$targetFile = realpath($directory).'/'. ($rename !== '' ? $rename : $file['name']);
			
			// Validate the filetype
			$extension = strtolower(pathinfo($file['name'])['extension']);
			if(in_array($extension, $allowedExt)){
				// Save the file
				move_uploaded_file($file['tmp_name'], $targetFile);
				return true;
			} 
			
			// The file type wasn't allowed
			else 
				return false;
		}
		return false;
	}
}