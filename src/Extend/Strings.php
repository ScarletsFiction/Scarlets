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
		if (is_array($d)) 
			foreach ($d as $k => $v) 
				$d[$k] = utf8ize($v);

		else if(is_object($d))
			foreach ($d as $k => $v) 
				$d->$k = utf8ize($v);

		else 
			return iconv('UTF-8', 'UTF-8//IGNORE', $d);

		return $d;
	}

	public static function random($length=6){
		$str = "";
		$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
		$max = count($characters) - 1;
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, $max);
			$str .= $characters[$rand];
		}
		return $str;
	}

	public static function randomNumber(){
		return time().rand(100,999);
	}
}