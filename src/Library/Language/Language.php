<?php
	namespace Scarlets\Library\Language;
	use \Scarlets;

	/*
		> Initialize
	
		(id) Storage ID that configured on the application
	*/
	function load($languageID){
		$reg = &Scarlets::$registry;
		$path = $reg['path.lang'].$languageID.'/';
		if(file_exists($path)){
			$reg['Language.'.$languageID] = [];
			$ref = &$reg['Language.'.$languageID];
			$list = glob($path.'*.php');

			foreach ($list as &$file) {
				$keys = include $file;
				$name = basename($file);
				$name = substr($name, 0, strlen($name)-4);
				foreach ($keys as $key => $value) {
					$ref[$name.'.'.$key] = $value;
				}
			}
			return;
		}
		throw new Exception("LanguageID not exist".$languageID, 1);
	}

	function get($key, $values = [], $languageID=0){
		$reg = &Scarlets::$registry;
		if(!$languageID) $languageID = $reg['config']['app.language'];

		if(!isset($reg['Language.'.$languageID]))
			load($languageID);

		$value = $reg['Language.'.$languageID][$key];
		for ($i=0; $i < count($values); $i++) { 
			$value = str_replace('(:'.$i.')', $values[$i], $value);
		}
		return $value;
	}