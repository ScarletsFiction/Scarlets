<?php 
namespace Scarlets\Library;
use \Scarlets;

class WebRequest{
	public static function loadURL($url, $data="")
	{
		$userAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36';
		
		$headers = [];
		$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'; 
		$headers[] = 'Accept-Language: en-US,en;q=0.5'; 
		$headers[] = 'Connection: keep-alive';
		
		$ch = curl_init($url);
		
		if($data!="")
		{
			if(isset($data['ssl'])) //0,1
			{
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $data['ssl']);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $data['ssl']);
			}

			if(isset($data['header']))
				$headers = array_merge($headers,$data['header']);

			if(isset($data['referer']))
				$headers[] = 'Referer: '.$data['referer'];

			if(isset($data['post'])) //['post' => 'datahere']  -- not html encoded
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data['post']);
			}

			if(isset($data['cookiefile']))
			{
				if(!file_exists($data['cookiefile']))
					file_put_contents($data['cookiefile'], '');
				curl_setopt($ch, CURLOPT_COOKIE, file_get_contents($data['cookiefile']));
			}

			if(isset($data['cookie']))
				curl_setopt($ch, CURLOPT_COOKIE, $data['cookie']);

			if(isset($data['limitSize'])) //in KB
			{
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // more progress info
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function(
				$DownloadSize, $Downloaded, $UploadSize, $Uploaded
				){
					// If $Downloaded exceeds 1KB, returning non-0 breaks the connection!
					return ($Downloaded > ($data['limitSize'] * 1024)) ? 1 : 0;
				});
			}

			if(isset($data['proxy']))
			{
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
				curl_setopt($ch, CURLOPT_PROXY, $data['proxy']['ip']);
				curl_setopt($ch, CURLOPT_PROXYPORT, $data['proxy']['port']);    
				if(isset($data['userpass']))
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data['proxy']['userpass']);
			}
		}
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip"); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		if(isset($data['returnheader']) || isset($data['cookiefile']))
			curl_setopt($ch, CURLOPT_HEADER, 1);
		
		$result = curl_exec($ch);
		if(isset($data['utf8']))
			$result = mb_convert_encoding($result, 'utf-8', 'shift-jis');

		if($data !== "" && (isset($data['returnheader']) || isset($data['cookiefile']))) //0,1
		{
			$err = curl_errno( $ch );
			$errmsg = curl_error( $ch );
			$header = curl_getinfo( $ch );
			$myHeader = $header['request_header'];
			curl_close( $ch );
		
			$header_content = substr($result, 0, $header['header_size']);
			$body_content = trim(str_replace($header_content, '', $result));
			$pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
			preg_match_all($pattern, $header_content, $matches); 
			$cookiesOut = implode("; ", $matches['cookie']);
			
			if(isset($data['cookiefile']))
			{
				if(strlen($cookiesOut)>=2){file_put_contents($data['cookiefile'], $cookiesOut);}
				return $body_content;
			}
			
			$header['errno'] = $err;
			$header['errmsg'] = $errmsg;
			$header['headers'] = $header_content;
			$header['content'] = $body_content;
			$header['cookies'] = $cookiesOut;
			return $header;
		}
		
		curl_close($ch);
		return $result;
	}

	// From this server to client browser
	public static function giveFiles($filePath, $fileName = null)
	{
		if(is_file($filePath))
		{
			$fileTime = filemtime($filePath);
  			$time = date('r', $fileTime);

			session_cache_limiter('none');
			header("Pragma: public");
			header("Cache-Control: public");
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

		    header("Content-Type: " . $ctype);
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
				header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
				header('Content-Length: '.($seek_end - $seek_start + 1));
			}
			else{
				header("Content-Length: $file_size");
				readfile($filePath);
				exit;
			}
		    
			$file = @fopen($filePath,"rb");
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
		header("HTTP/1.0 500 Internal Server Error");
		exit;
	}
	
	// From other server to local file
	public static function download($from, $to, $type="curl")
	{
		if($type=="socket")
		{
			#chunk = 10MB
			$chunksize = 10 * (1024 * 1024);
			
			if(((string)get_http_response_code($from))!='200'){return false;}
		
			# parse_url breaks a part a URL into it's parts, i.e. host, path,
			# query string, etc.

			$parts = parse_url($from);
			$i_handle = fsockopen($parts['host'], 80, $errstr, $errcode, 5);
			$o_handle = fopen($to, 'wb');
		
			if ($i_handle == false || $o_handle == false) {
				return false;
			}
		
			if (!empty($parts['query'])) {
				$parts['path'] .= '?' . $parts['query'];
			}
		
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
				if ($line == "\r\n") break;
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
				if ($bytes == false) {
					return false;
				}
				$cnt += $bytes;
			
				#We're done reading when we've reached the conent length
				if ($cnt >= $length) break;
			}
		
			fclose($i_handle);
			fclose($o_handle);
			return $cnt;	
		}
		
		elseif($type=="direct"){
			$rh = fopen($from, 'rb');
			$wh = fopen($to, 'wb');
			if (!$rh || !$wh) {
				return false;
			}

			while (!feof($rh)) {
				if (fwrite($wh, fread($rh, 1024)) === FALSE) {
					return false;
				}
			}
			fclose($rh);
			fclose($wh);
			return true;
		}

		elseif($type=="curl"){
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
			return true;
		}
	}
	
	// allowedExt = array
	// From client browser to this server
	public static function receiveFile($directory, $allowedExt, $rename='')
	{
		if(!empty($_FILES)) 
		{
			$file = &$_FILES['Filedata'];
			$targetFile = realpath($directory).'/'. ($rename !== '' ? $rename : $file['name']);
			
			// Validate the filetype
			$extension = strtolower(pathinfo($file['name'])['extension']);
			if (in_array($extension, $allowedExt)) 
			{
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