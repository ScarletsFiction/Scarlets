<?php
namespace Scarlets\Library;
use \Scarlets;

/*
	It would be better if language translation are
	handled by the browser instead of server
*/
class Language{
	public static $default = '';
	public static $loaded = [];
	private static $loaded_ = [];

	/*
		> Initialize
	
		(id) Storage ID that configured on the application
	*/
	public static function &load($languageID, $file=0){
		$loaded = &self::$loaded;
		if(in_array("$languageID$file", self::$loaded_))
			return $loaded[$languageID];

		self::$loaded_[] = "$languageID$file";

		$path = Scarlets::$registry['path.lang']."/$languageID/";
		if(file_exists($path)){
			$loaded[$languageID] = [];
			$ref = &$loaded[$languageID];

			// Single load
			if($file !== 0 && !Scarlets::$isConsole){
				$empty = '';

				// Protect from path climbing
				$file = str_replace(['./', '../'], $empty, $file);

				if(!file_exists("$path/$file.php"))
					return $empty;

				$keys = include "$path$file.php";
				foreach ($keys as $key => $value) {
					// Sub key 1
					if(is_array($value)){
						foreach ($value as $key2 => $value2) {
							$ref["$file.$key.$key2"] = $value2;
						}
						continue;
					}

					$ref["$file.$key"] = $value;
				}
				return $ref;
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
							$ref["$file.$key.$key2"] = $value2;
						}
						continue;
					}

					$ref["$file.$key"] = $value;
				}
			}
			return $ref;
		}
		trigger_error("LanguageID not exist: $languageID", 1);
	}

	public static function &get($key, $values = [], $languageID=false){
		$loaded = &self::$loaded;
		if($languageID === false)
			$languageID = &self::$default;

		// Check if language file are loaded
		if(!isset($loaded[$languageID]) || !isset($loaded[$languageID][$key]))
			self::load($languageID, explode('.', $key)[0]);

		$ret = &$loaded[$languageID][$key];
		foreach ($values as $key => &$value) {
			$ret = str_replace("{$key}", $value, $ret);
		}

		return $ret;
	}
}

Language::$default = Scarlets\Config::$data['app.language'];