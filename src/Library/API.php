<?php
namespace Scarlets\Library;
use \Scarlets\Route\Serve;

class API{
	public static function fields(&$from, $array){
		if(isset($_REQUEST['fields'])){
			$requested = explode(',', str_replace(' ', '', $_REQUEST['fields']));
			for ($i=0; $i < count($requested); $i++) {
				if(!in_array($requested[$i], $array))
					array_splice($requested, $i, 1);
			}

			$array = &$requested;
		}

		if(!$from)
			$from = $array;
		else $from = array_flip(array_flip(array_merge($from, $array)));
	}

	public static function &request($field, $default = null){
		$type = null;
		if(is_array($field)){
			$key = array_keys($field)[0];
			$type = &$field[$key];
			$field = &$key;
		}

		if(isset($_REQUEST[$field])){
			if($type !== null){
				if($type === 'number' && is_numeric($_REQUEST[$field])){
					$_REQUEST[$field] = $_REQUEST[$field] + 0;
					return $_REQUEST[$field];
				}

				if($type === 'array' && is_array($_REQUEST[$field]))
					return $_REQUEST[$field];

				elseif($type === 'bool'){
					$value = null;
					if($_REQUEST[$field] === 'true' || $_REQUEST[$field] === '1')
						$value = true;

					elseif($_REQUEST[$field] === 'false' || $_REQUEST[$field] === '0')
						$value = false;

					if($value !== null)
						return $value;

					$type = 'boolean';
				}

				Serve::end("{\"error\":\"'$field' must be a $type\"}");
			}
			return $_REQUEST[$field];
		}

		if($default !== null)
			return $default;

		Serve::end("{\"error\":\"'$field' are required\"}");
	}

	public static function alias(&$store, $fields, $sanitizer = false){
		if(!$store)
			$store = [];

		foreach ($fields as $key => $value) {
			if(isset($_REQUEST[$key])){
				if($sanitizer === false)
					$store[$value] = &$_REQUEST[$key];
				else $store[$value] = $sanitizer($_REQUEST[$key]);
			}
		}
	}

	public static function obtain(&$store, $fields, $sanitizer = false){
		if(!$store)
			$store = [];

		foreach ($fields as &$key) {
			if(isset($_REQUEST[$key])){
				if($sanitizer === false)
					$store[$key] = &$_REQUEST[$key];
				else $store[$key] = $sanitizer($_REQUEST[$key]);
			}
		}
	}
}