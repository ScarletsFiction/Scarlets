<?php
namespace Scarlets\Extend;

class Strings{
	public static function formatBytes($bytes, $precision = 2){
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
	
		return round($bytes, $precision) . '' . $units[$pow];
	}

	public static function utf8ize($d){
		if(is_array($d)){
			$k = array_keys($d);
			for ($i=0; $i < count($k); $i++) { 
				$d[$k[$i]] = utf8ize($d[$k[$i]]);
			}
		}

		else if(is_object($d)){
			$k = array_keys($d);
			for ($i=0; $i < count($k); $i++) { 
				$d->$k[$i] = utf8ize($d->$k[$i]);
			}
		}

		else 
			return iconv('UTF-8', 'UTF-8//IGNORE', $d);

		return $d;
	}

	public static function &random($length=6, $withSymbol=false){
		$str = "";
		$characters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
		'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W',
		'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
		'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w',
		'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

		if($withSymbol)
		$characters = array_merge($characters, ['`', '~', '!', '@', '#', '$',
			'%', '^', '&', '*', '(', ')', '_', '-', '+', '=', '{', '[', '}', 
		':', ';', '|', '\\', '"', '\'', '<', ',', '>', '.', '/', '?', ' ']);

		$max = count($characters) - 1;
		for($i = 0; $i < $length; $i++){
			$rand = mt_rand(0, $max);
			$str .= $characters[$rand];
		}
		return $str;
	}

	public static function randomNumber(){
		return time().rand(100,999);
	}
}