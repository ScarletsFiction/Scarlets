<?php
namespace Scarlets\Library\FileSystem;
use \Scarlets\Config;
use \Scarlets\Route;
use \Scarlets\Route\Serve;
use \Scarlets\Extend\Strings;
use \Scarlets\Library\WebRequest;

/*
---------------------------------------------------------------------------
| RemoteFile Library
---------------------------------------------------------------------------
|
| By using this library you're able to transfer or receive files from
| another server with Scarlets Framework.
|
*/
class RemoteFile{
	public static $storage = [];
	public static $listener = [];

	public static function init(){
		$config = Config::load('filesystem');
		self::$storage = &$config['filesystem.storage'];
	}

	private static function &targetHost(&$path, &$ref = false){
		$path = explode('}', $path);
		$credential = substr($path[0], 1);
		$ref = &self::$storage[$credential];
		$path = $path[1];

		if(isset($ref['host']) === false)
			throw new Exception("Can't use `$credential` as a remote client", 1);
		return $ref['host'];
	}

	private static function realpath(&$path){
		$path = explode('}', $path);
		$ref = &self::$storage[substr($path[0], 1)];
		return $ref['path'].$path[1];
	}

	// ===== Client =====
	private static function transmit($target, $action, $args, $prefixed){
		return WebRequest::loadURL($target, [
			'method' => 'JSON_POST',
			'data'=>[
				'action'=>$action,
				'args'=>$args,
				'prefixed'=>$prefixed
			]
		]);
	}

	private static function fileUpload($action, &$path, &$value, &$isFile){
		$target = self::targetHost($path);

		if($isFile !== false)
			$value = new \CURLFile(self::realpath($value));

		return self::transmit($target, $action, [$path, $value], '0');
	}

	public static function put($path, $value, $isFile = false){
		return self::fileUpload('put', $path, $value, $isFile);
	}

	public static function append($path, $value, $isFile = false){
		return self::fileUpload('append', $path, $value, $isFile);
	}

	public static function prepend($path, $value, $isFile = false){
		return self::fileUpload('prepend', $path, $value, $isFile);
	}

	public static function delete($path, $recursive = false){
		$target = self::targetHost($path);
		return self::transmit($target, 'delete', [$path, $recursive], '0');
	}

	public static function move($path, $to){
		$target = self::targetHost($path);
		return self::transmit($target, 'move', [$path, $to], '0|1');
	}

	public static function search($path, $regex, $recursive = false){
		$target = self::targetHost($path);
		return self::transmit($target, 'search', [$path, $regex, $recursive], '0');
	}

	public static function copy($path, $to){
		$target = self::targetHost($path);
		return self::transmit($target, 'copy', [$path, $to], '0|1');
	}

	public static function size($path){
		$target = self::targetHost($path);
		return self::transmit($target, 'size', [$path], '0');
	}

	public static function lastModified($path){
		$target = self::targetHost($path);
		return self::transmit($target, 'lastModified', [$path], '0');
	}

	public static function download($path, $to){
		$target = self::targetHost($path, $ref);
		return WebRequest::download([$target => self::realpath($to)], [
			'method'=>'JSON_POST',
			'data'=>['key'=>$ref['key'], 'action'=>'download', 'args'=>$path, 'prefixed'=>'0']
		]);
	}

	public static function load($path){
		$target = self::targetHost($path);
		return self::transmit($target, 'read', [$path]);
	}

	// ===== Server =====
	public static function route($url, $storage){
		$credential = &self::$storage[$storage];
		$prefix = '{'.$credential['storage'].'}';
		$driver = &self::$storage[$credential['storage']]['driver'];

		if($driver === 'localfile')
			$driver = '\Scarlets\Library\FileSystem\LocalFile::';

		Route::post($url, function()use(&$ref, &$driver, &$prefix, &$storage){
			if(
				// Check credentials
				(isset($_POST['key']) === false || $_POST['key'] !== $credential['key'])
				&&

				// Check invalid character
				preg_match('/[^0-9a-zA-Z_]/', $_POST['action']) !== false
				&&

				// Check IP Address
				(isset($credential['allow-ip']) !== false && (
					is_array($credential['allow-ip']) ? 
						($credential['allow-ip'] !== $_SERVER['REMOTE_ADDR']) : (!in_array($_SERVER['REMOTE_ADDR'], $credential['allow-ip'])
					)
				))
			){
				Serve::status(403);
				exit;
			}

			$action = $_POST['action'];
			$callable = "$driver$action";
			if(is_callable($callable) === false){
				Serve::status(404);
				exit;
			}

			$prefixed = explode('|', $_POST['prefixed']);
			foreach ($prefixed as &$value) {
				$_POST['args'][$value] = $prefix.$_POST['args'][$value];
			}

			// Trigger listener
			$listener = &self::$listener;
			if(isset($listener[$storage]) !== false){
				$listener = &$listener[$storage];
				$obj = $_POST['args'];

				if(isset($listener[$action]) !== false){
					$one = &$listener[$action];
					foreach ($one as &$value) {
						call_user_func_array($value, $obj);
					}
				}

				if(isset($listener['*']) !== false){
					$all = &$listener['*'];
					array_unshift($obj, $action);

					foreach ($all as &$value) {
						call_user_func_array($value, $obj);
					}
				}
			}

			if($action === 'download'){
				WebRequest::giveFiles(LocalFile::realpath($prefix.$_POST['args'][0]));
				exit;
			}

			// Pass to handler
			return call_user_func_array($callable, $_POST['args']);
		});
	}

	public static function listen($storage, $which, $callback){
		$ref = &self::$listener;

		if(isset($ref[$storage]) === false)
			$ref[$storage] = [];

		if(isset($ref[$storage][$which]) === false)
			$ref[$storage][$which] = [];

		$ref[$storage][$which][] = $callback;
	}
}
RemoteFile::init();