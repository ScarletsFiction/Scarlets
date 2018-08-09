<?php
namespace Scarlets\Library;
use \Scarlets;

/*
	It would be better if language translation are
	handled by the browser instead of server
*/
class Language{
	public static $current = '';
	public static $loaded = [];

	/*
		> Initialize
	
		(id) Storage ID that configured on the application
	*/
	public static function load($languageID, $file=0){
		$path = Scarlets::$registry['path.lang'].'/'.$languageID.'/';
		if(file_exists($path)){
			$loaded = &self::$loaded;
			$loaded[$languageID] = [];
			$ref = &$loaded[$languageID];

			// Single load
			if($file !== 0 && !Scarlets::$isConsole){
				$keys = include $path.$file.'.php';
				foreach ($keys as $key => $value) {

					// Sub key 1
					if(is_array($value)){
						foreach ($value as $key2 => $value2) {
							$ref[$file.'.'.$key.'.'.$key2] = $value2;
						}
						continue;
					}

					$ref[$file.'.'.$key] = $value;
				}
				return;
			}

			// Else, load all
			$list = glob($path.'*.php');
			foreach ($list as &$file) {
				$keys = include $file;

				$file = basename($file);
				$file = substr($file, 0, -4);
				foreach ($keys as $key => $value) {

					// Sub key 1
					if(is_array($value)){
						foreach ($value as $key2 => $value2) {
							$ref[$file.'.'.$key.'.'.$key2] = $value2;
						}
						continue;
					}

					$ref[$file.'.'.$key] = $value;
				}
			}
			return;
		}
		throw new \Exception("LanguageID not exist: ".$languageID, 1);
	}

	public static function &get($key, $values = [], $languageID=0){
		$loaded = &self::$loaded;
		if(!$languageID) $languageID = self::$current;

		// Check if language file are loaded
		if(!isset($loaded[$languageID]) || !isset($loaded[$languageID][$key]))
			self::load($languageID, explode('.', $key)[0]);

		$value = $loaded[$languageID][$key];
		for ($i=0; $i < count($values); $i++) { 
			$value = str_replace('(:'.$i.')', $values[$i], $value);
		}

		return $value;
	}
}

Language::$current = Scarlets\Config::$data['app.language'];