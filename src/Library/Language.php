<?php
namespace Scarlets\Library;
use \Scarlets;
use \Scarlets\Library\FileSystem\LocalFile;
use \Exception;

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

		// Protect from path climbing
		$path = Scarlets::$registry['path.lang']."/$languageID/";
		LocalFile::realpath($path);

		if(file_exists($path)){
			$loaded[$languageID] = [];
			$ref = &$loaded[$languageID];

			// Single load
			if($file !== 0 && !Scarlets::$isConsole){
				$empty = '';

				if(!file_exists("$path/$file.php"))
					return $empty;

				$keys = include "$path$file.php";
				$ref[$file] = $keys;
				return $ref[$file];
			}

			// Else, load all
			$list = glob($path.'*.php');
			foreach ($list as &$file) {
				$keys = include $file;

				$file = basename($file);
				$file = substr($file, 0, -4);
				$ref[$file] = &$keys;
			}
			return $ref;
		}
		throw new Exception("LanguageID not exist: $languageID", 1);
	}

	public static function get($key, $values=null, $languageID=null){
		$loaded = &self::$loaded;
		if($languageID === null)
			$languageID = &self::$default;

		$key = explode('.', $key);

		// Check if language file are loaded
		if(!isset($loaded[$languageID]) || !isset($loaded[$languageID][$key[0]]))
			self::load($languageID, $key[0]);

		$ret = &$loaded[$languageID];
		foreach ($key as &$k){
			if(isset($ret[$k]) === false)
				return '';

			$ret = &$ret[$k];
		}

		if($values !== null)
			foreach ($values as $key => &$value) {
				$ret = str_replace("{{$key}}", $value, $ret);
			}

		return $ret;
	}
}

Language::$default = Scarlets\Config::$data['app.language'];