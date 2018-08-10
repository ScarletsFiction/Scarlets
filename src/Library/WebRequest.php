<?php 
namespace Scarlets\Library;
use \Scarlets;

class WebRequest{
	public static function loadURL($urlx, $data="")
	{
		$url = str_replace('\\', '/', $urlx);
		$url = str_replace(' ', '%20', $url);
		
		$config['useragent'] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36';
		
		$headers = array();
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
			if(isset($data['header'])){$headers = array_merge($headers,$data['header']);}
			if(isset($data['referer'])){$headers[] = 'Referer: '.$data['referer'];}
			if(isset($data['post'])) //['post' => 'datahere']  -- not html encoded
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data['post']);
			}
			if(isset($data['cookiefile']))
			{
				//curl_setopt($ch, CURLOPT_COOKIEJAR, $data['cookiefile']);
				//curl_setopt($ch, CURLOPT_COOKIEFILE, $data['cookiefile']);
				if(!file_exists($data['cookiefile'])) file_put_contents($data['cookiefile'], '');
				curl_setopt($ch, CURLOPT_COOKIE, file_get_contents($data['cookiefile']));
			}
			if(isset($data['cookie']))
			{
				curl_setopt($ch, CURLOPT_COOKIE, $data['cookie']);
			}
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
				if(isset($data['userpass'])) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $data['proxy']['userpass']);
			}
		}
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip"); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $config['useragent']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		if(isset($data['returnheader'])||isset($data['cookiefile'])){curl_setopt($ch, CURLOPT_HEADER, 1);}
		
		$result = curl_exec($ch);
		if(isset($data['utf8'])) $result = mb_convert_encoding($result, 'utf-8', 'shift-jis');
		if($data!="")
		{
			if(isset($data['returnheader'])||isset($data['cookiefile'])) //0,1
			{
				$err     = curl_errno( $ch );
				$errmsg  = curl_error( $ch );
				$header  = curl_getinfo( $ch );
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
				
				$header['errno']   = $err;
				$header['errmsg']  = $errmsg;
				$header['headers']  = $header_content;
				$header['content'] = $body_content;
				$header['cookies'] = $cookiesOut;
				return $header;
			}
		}
		
		curl_close( $ch );
		return $result;
	}
}