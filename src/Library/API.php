<?php
namespace Scarlets\Library;

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
}