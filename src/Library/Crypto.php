<?php
namespace Scarlets\Library;
use \Scarlets;
use Scarlets\Config;

class Crypto{
	public static $key = '';
	public static $cipher = '';

	// Random/Unique crypto mask
	// Make sure it's different each other
	public static $mask = [];
	public static $sify_mask = [];
	public static $MD5_mask = [];
	public static $crypto_mask = [];

	public static function &encrypt($str, $pass=false, $cipher=false, $mask=false){
		if(!$pass) $pass = &self::$key;
		if(!$cipher) $cipher = &self::$cipher;

		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext = openssl_encrypt($str, $cipher, $pass, OPENSSL_RAW_DATA, $iv);

		// An initialization vector has different security requirements than a key, so the IV usually does not need to be secret.
		// However, in most cases, it is important that an initialization vector is never reused under the same key.
		// https://en.wikipedia.org/wiki/Block_cipher_mode_of_operation#Initialization_vector_.28IV.29
		$str = base64_encode($iv.':~:'.$ciphertext);

		if($mask && self::$crypto_mask){
			$ref = &self::$crypto_mask;
			$str = &$str;
			foreach($ref as $key => $value){
				$str = str_replace($key, $value[count($value) - 1], $str);
			}
		}
		return $str;
	}

	public static function decrypt($str, $pass=false, $cipher=false, $mask=false){
		if(!$pass) $pass = &self::$key;
		if(!$cipher) $cipher = &self::$cipher;

		if($mask && self::$crypto_mask){
			$ref = &self::$crypto_mask;
			foreach($ref as $key => $value){
				$str = str_replace($value, $key, $str);
			}
		}

		$data = explode(':~:', base64_decode($str));
		return openssl_decrypt($data[1], $cipher, $pass, OPENSSL_RAW_DATA, $data[0]);
	}

	// SFSessionID mask
	public static function &Sify($astr){
		$ref = &self::$sify_mask;
		$str = &$astr;

		// Apply mask
		foreach($ref as $key => $value){
			$str = str_replace($key, $value, $str);
		}

		// Random Lowercase
		$lowered = false;
		for ($i=0; $i < strlen($str); $i++) { 
			$str_ = strtolower($str[$i]);
			if($str_ == $str[$i])
				continue;

			if(!$lowered){
				$str[$i] = $str_;
				$lowered = true;
			}

			else $lowered = false;
		}

		return $str;
	}

	// SFSessionID unmask
	public static function &dSify(&$astr){
		$ref = &self::$sify_mask;
		$str = strtoupper($astr);
		foreach($ref as $key => $value){
			$str = str_replace($value, $key, $str);
		}
		return $str;
	}
	
	// MD5 mask
	public static function exMD5($str_){
		$ref = &self::$MD5_mask;
		$str = strtolower(md5($str_));
		foreach($ref as $key => $value){
			$str = str_replace($key, $value, $str);
		}
		return $str;
	}

	public static function init(){
		$ref = Config::load('security');
		self::$key = &$ref['security.key'];
		self::$cipher = &$ref['security.cipher'];
		self::$crypto_mask = &$ref['security.crypto_mask'];
		self::$sify_mask = &$ref['security.sify_mask'];
		self::$MD5_mask = &$ref['security.MD5_mask'];
	}
}
Crypto::init();