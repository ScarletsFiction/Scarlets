<?php 
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Library\FileSystem\LocalFile;
use \Scarlets\Extend\MimeType;

class WebRequest{
	public static $userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Safari/537.36';
	public static function &loadURL($url, $options = false){
		$options_ = [
			CURLOPT_ENCODING=>'gzip',
			CURLOPT_USERAGENT=>self::$userAgent,
			CURLOPT_RETURNTRANSFER=>1,
			CURLOPT_FOLLOWLOCATION=>1,
			CURLOPT_MAXREDIRS=>5,
			CURLOPT_AUTOREFERER=>1,
			CURLOPT_SSL_VERIFYHOST=>0,
			CURLOPT_SSL_VERIFYPEER=>0
		];

		$headers = ['Accept'=>'*/*;q=0.8'];

		if($options)
			self::implementCURLOptions($options_, $options, $headers, $url);

		$header = [];
		foreach ($headers as $key => &$value)
			$header[] = "$key: $value";
		$options_[CURLOPT_HTTPHEADER] = &$header;

		$preprocess = function($result, &$ch) use(&$options) {
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
		};

		# Execute CURL
		if(is_array($url) === false){
			$ch = curl_init($url);
			curl_setopt_array($ch, $options_);
			$results = $preprocess(curl_exec($ch), $ch);
			return $results;
		}
		else {
			$mh = curl_multi_init(); $ch = [];
			foreach ($url as $key => &$value) {
				$ch[$key] = curl_init($value);
				curl_setopt_array($ch[$key], $options_);
				curl_multi_add_handle($mh, $ch[$key]); 
			}
			self::multiHandleCURL($mh, $ch, false, false);

			$results = [];
			foreach ($ch as $key => &$value) {
				$results[$key] = $preprocess(curl_multi_getcontent($value), $value);
			}
			return $results;
		}
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
		    $ctype = MimeType::getMimeType($file_ext);

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
	public static function download($path, $options = false){
		$onProgress = false;

		$options_default = ['connection' => 4];
		if($options !== false)
			$options = array_merge($options_default, $options);
		else $options = &$options_default;

		$connection = &$options['connection'];
		$connection--;

		$mh = curl_multi_init();
		$options_ = [
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_RETURNTRANSFER => 0,
			CURLOPT_BINARYTRANSFER => 1,
			CURLOPT_FRESH_CONNECT => 0,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_USERAGENT => self::$userAgent
		];

		$headers = ['Accept'=>'*/*;q=0.8'];
		$getRequest = '';

		if($options)
			self::implementCURLOptions($options_, $options, $headers, $getRequest);

		$header = [];
		foreach ($headers as $key => &$value)
			$header[] = "$key: $value";
		$options_[CURLOPT_HTTPHEADER] = &$header;

		$addRequest = function(&$url, &$path) use(&$mh, &$options_, &$connection) {
			$size = self::contentSize($url);

			if($connection > 0){
				$splits = range(0, $size, round($size / $connection));
				$splitSize = count($splits);
			}
			else $splits = $splitSize = 1;

			$parts = []; $fh = []; $ch = [];
			for ($i = 0; $i < $splitSize; $i++) {
			    $parts[$i] = $path."$i.tmp";

			    $ch[$i] = curl_init($url);
			    curl_setopt_array($ch[$i], $options_);

			    $fh[$i] = fopen($parts[$i], 'w+');
			    curl_setopt($ch[$i], CURLOPT_FILE, $fh[$i]);

			    if($connection > 0){
				    $range = ($i === 0 ? 0 : $splits[$i] + 1).'-'.($i === $splitSize - 1 ? $size : $splits[$i + 1]);
				    curl_setopt($ch[$i], CURLOPT_RANGE, $range);
				}

			    curl_multi_add_handle($mh, $ch[$i]); 
			}

			return [[$parts, $fh, $path], $ch];
		};

		$reqs = []; $ch = [];
		foreach ($path as $url => &$path) {
			if($getRequest !== ''){
				if(strpos($url, '?') !== false)
					$url .= '&'.substr($getRequest, 1);
				else $url .= $getRequest;
			}

			$tmp = $addRequest($url, $path);
			$reqs[] = $tmp[0];
			$ch[] = $tmp[1];
		}

		self::multiHandleCURL($mh, $ch, $onProgress);

		$sizes = [];
    	foreach ($reqs as &$req) {
			$parts = &$req[0]; $fh = &$req[1]; $to = &$req[2];

			$dest = fopen($to, "w+");
			for ($i = 0, $n = count($parts); $i < $n; $i++) {
				fseek($fh[$i], 0, SEEK_SET);

				# Pipe stream
				while (feof($fh[$i]) === false) {
				    fwrite($dest, fread($fh[$i], 65535));
				}
				fclose($fh[$i]);
			    unlink($parts[$i]);
			}
			fclose($dest);
			$sizes[] = filesize($to);
    	}

		return $sizes;
	}

	private static function multiHandleCURL(&$mh, &$chs, $onProgress, $closeCurl = true){
		do {
		    curl_multi_exec($mh, $running);
		    curl_multi_select($mh);

		    if($onProgress !== false){
    			$info = curl_multi_info_read($mh);
    			if($info !== false) continue;

    			foreach ($chs as &$ch) {
			    	$i = array_search($info['handle'], $ch);
			    	if($i === -1) continue;

			    	$k = array_search($ch, $chs);

			    	$info = curl_getinfo($info['handle']);
			    	$onProgress($k, $i, $info['size_download'], $info['download_content_length'], $info['speed_download']);
			    	break;
    			}
		    }
		} while ($running > 0);

		# Close handles
		foreach ($chs as &$ch) {
			if($closeCurl === true){
				foreach ($ch as &$value) {
					curl_multi_remove_handle($mh, $value);
					curl_close($value);
				}
			}
			else curl_multi_remove_handle($mh, $ch);
		}
		curl_multi_close($mh);
	}

	private static function implementCURLOptions(&$options_, &$options, &$headers, &$url){
		if(isset($options['ssl'])){ # 0, 1
			$options_[CURLOPT_SSL_VERIFYHOST] = &$options['ssl'];
			$options_[CURLOPT_SSL_VERIFYPEER] = &$options['ssl'];
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
					$options_[CURLOPT_POST] = 1;
					$options_[CURLOPT_POSTFIELDS] = &$options['data'];
				}

    			// Encode the data as JSON and send on the body
				elseif($options['method'] === 'JSON_POST'){
					$options_[CURLOPT_CUSTOMREQUEST] = 'POST';

					$headers['Content-Type'] = 'application/json; charset=utf-8';
					$options_[CURLOPT_POSTFIELDS] = json_encode($options['data']);
				}

				else $options_[CURLOPT_CUSTOMREQUEST] = &$options['method'];
			}

			elseif($options['method'] === 'HEAD')
				$options_[CURLOPT_NOBODY] = 1;

    		else $options_[CURLOPT_CUSTOMREQUEST] = &$options['method'];
    	}

    	// Default GET parameter
		else if(isset($options['data'])){ # ['field' => 'value']
			$url .= '?'.http_build_query($options['data']);
		}

		if(isset($options['cookiefile'])){ # 'path/to/file'
			if(!file_exists($options['cookiefile']))
				file_put_contents($options['cookiefile'], '');

			$options_[CURLOPT_COOKIE] = file_get_contents($options['cookiefile']);
		}

		if(isset($options['cookie'])) # 'field=value;data=values'
			$options_[CURLOPT_COOKIE] = &$options['cookie'];

		if(isset($options['limitSize'])){ # in KB
			$options_[CURLOPT_NOPROGRESS] = false;
			$options_[CURLOPT_PROGRESSFUNCTION] = function(
				$resource, $DownloadSize, $Downloaded, $UploadSize, $Uploaded
			) use(&$options) {
				// If $Downloaded exceeds 1KB, returning non-0 breaks the connection!
				return ($Downloaded > ($options['limitSize'] * 1024)) ? 1 : 0;

				if(isset($options['progress']))
					$options['progress']($resource, $DownloadSize, $Downloaded, $UploadSize, $Uploaded);
			};
		}

		elseif(isset($options['progress'])){ # in KB
			$options_[CURLOPT_NOPROGRESS] = false;
			$options_[CURLOPT_PROGRESSFUNCTION] = $options['progress'];
		}

		if(isset($options['buffer'])) # in Byte
			$options_[CURLOPT_BUFFERSIZE] = $options['buffer'];

		if(isset($options['proxy'])){ # [ip=>127.0.0.1, port=>3000, *userpass=>'user:pass']
			$options_[CURLOPT_PROXYAUTH] = CURLAUTH_NTLM;
			$options_[CURLOPT_PROXY] = &$options['proxy']['ip'];
			$options_[CURLOPT_PROXYPORT] = &$options['proxy']['port'];   

			if(isset($options['userpass']))
				$options_[CURLOPT_PROXYUSERPWD] = &$options['proxy']['userpass'];
		}

		if(isset($options['returnheader']) || isset($options['cookiefile']))
			$options_[CURLOPT_HEADER] = 1;
	}

	// allowedTypes = array
	// From client browser to this server
	public static function receiveFile($field, $directory, $allowedTypes, $rename = ''){
		if(!empty($_FILES)){
			if($directory === ''){
				\Scarlets\Log::message("Can't save file because the directory path was empty");
				return false;
			}

			if(substr($directory, -1) !== '/')
				$directory += '/';

			$isArray = false;
			$fileName = &$_FILES[$field]['name'];
			$fileTemp = &$_FILES[$field]['tmp_name'];

			if(is_array($fileName) === false){
				$fileName = [$fileName];
				$fileTemp = [$fileTemp];
			}
			else $isArray = true;

			for ($i=0, $n=count($fileName); $i < $n; $i++) { 
				$path = $directory.($rename !== '' ? ($isArray ? $i.$rename : $rename) : $fileName[$i]);

				// Remove invalid word character
				$real = strlen($path);
				$path = preg_replace('/[^\\pN\\pL.\\/:;\'"\\\\\/\\[\\]{}!@#$%^&*()_+\\-=|]+/', '', $path);

				// Use timestamp if no valid character left
				if(strlen($path) !== $real && ($path === '' || $path[0] === '.'))
					$path = time().rand(1,1e3).$path;

				// Validate save path
				LocalFile::realpath($path);

				// Validate the filetype
				if(MimeType::compare(mime_content_type($fileTemp[$i]), $allowedTypes)){
					// Save the file
					move_uploaded_file($fileTemp[$i], $path);
					continue;
				}

				// The file type wasn't allowed
				else return false;
			}

			return true;
		}
		return false;
	}

	public static function contentSize($url){
		$data = self::loadURL($url, ['method'=>'HEAD', 'returnheader'=>true]);
		if(isset($data['headers']['Content-Length']) === false)
			return 0;
		return (float)$data['headers']['Content-Length'];
	}

	public static function lastRedirection($url){
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);

		return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	}
}