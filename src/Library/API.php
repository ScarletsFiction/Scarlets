<?php
namespace Scarlets\Library;
use \Scarlets\Route\Serve;

class API{
	public static function fields($array){
		if(!isset($_REQUEST['fields'])) return $array;
		$requested = explode(',', str_replace(' ', '', $_REQUEST['fields']));

		for ($i=0; $i < count($requested); $i++) {
			if(!in_array($requested[$i], $array))
				array_splice($requested, $i, 1);
		}
		
		return $requested;
	}

	// ['required', 'optional' => 'default']
	public static function missing($array){
		foreach ($array as $key => $value) {
			if(is_numeric($key)){
				$value = explode('=', str_replace(' ', '', $value), 2);
				if(isset($_REQUEST[$value[0]])){
					if(isset($value[1])){
						if($value[1] === 'int' && !is_numeric($_REQUEST[$value[0]]))
							return $value[0];
						elseif(substr($value[1], 0, 1) === 'r' && !preg_match(substr($value[1], 1), $_REQUEST[$value[0]]))
							return $value[0];
					}
					continue;
				}
				return $value[0];
			}

			$_REQUEST[$key] = $value;
		}
		return false;
	}

	public static function request($field, $default = null){
		if(isset($_REQUEST[$field]))
			return $_REQUEST[$field];

		if($default !== null)
			return $default;

		Serve::end('{"error":"\''.$field.'\' are required"}');
	}
}