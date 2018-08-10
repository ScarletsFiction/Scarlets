<?php
namespace Scarlets\Library;
use \Scarlets;

class Crypto{
	public static $key = '';
	public static $cipher = '';

	// Random/Unique crypto mask
	// Make sure it's different each other
	public static $mask = [];

	public static function &encrypt($str, $pass=false, $cipher=false, $mask=true){
		if(!$pass) $pass = &self::$key;
		if(!$cipher) $cipher = &self::$cipher;

		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext = openssl_encrypt($str, $cipher, $pass, 0, $iv);
		$str = base64_encode($iv.':~:'.$ciphertext);

		if($mask && self::$mask){
			$ref = &self::$mask;
			$str = &$str;
			foreach($ref as $key => $value){
				$str = str_replace($key, $value[count($value) - 1], $str);
			}
		}
		return $str;
	}

	public static function decrypt($str, $pass=false, $cipher=false, $mask=true){
		if(!$pass) $pass = &self::$key;
		if(!$cipher) $cipher = &self::$cipher;

		if($mask && self::$mask){
			$ref = &self::$mask;
			foreach($ref as $key => $value){
				$str = str_replace($value, $key, $str);
			}
		}

		$data = explode(':~:', base64_decode($str));
		return openssl_decrypt($data[1], $cipher, $pass, 0, $data[0]);
	}

	public static function init(){
		$ref = &Scarlets::$registry['config'];
		self::$key = &$ref['app.key'];
		self::$cipher = &$ref['app.cipher'];
		self::$mask = &$ref['app.crypto_mask'];
	}
}
Crypto::init();