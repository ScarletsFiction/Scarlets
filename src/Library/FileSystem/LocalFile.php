<?php
namespace Scarlets\Library\FileSystem;
use \Scarlets\Extend\Strings;

/*
---------------------------------------------------------------------------
| LocalFile Library
---------------------------------------------------------------------------
|
| Most of the function of this library utilize the native function
| to process file
|
*/
class LocalFile{
	public static $storage = [];

	public static function init(){
		$config = Config::load('filesystem');
		self::$storage = &$config['filesystem.storage']['localfile'];
	}

	private static function realpath(&$path){
		$path = explode('}', $path);
		$path = self::$storage[substr($path[0], 1)].$path[1];
	}

	public static function load($path){
		if($path[0]==='{') self::realpath($path);

		return file_get_contents($path);
		/*
	    	$fhandle = fopen($path, "r");
	    	$fcontents = fread($fhandle, filesize($path));
	    	fclose($fhandle);
		*/
	}

	public static function size($path){
		if($path[0]==='{') self::realpath($path);

		if(is_dir($path)){
			$bytes = 0;
			$data = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($directory)));
			foreach ($data as $file)
			{
				$bytes += $file->getSize();
			}
			return $bytes;
		}
		if(!file_exists($path)) return 0;
		if(is_file($path))
			return filesize($path);
	}

	public static function append($path, $value){
		if($path[0]==='{') self::realpath($path);
		
		file_put_contents($path, $value, FILE_APPEND);
	}

	public static function prepend($path, $value){
		if($path[0]==='{') self::realpath($path);
		
		file_put_contents($path.'._temp', '');
		$fhandle = fopen($path.'._temp', 'w');
		fwrite($fhandle, $value);

		$oldFhandle = fopen($path, 'r');
		while (($buffer = fread($oldFhandle, 10000)) !== false) {
		    fwrite($fhandle, $buffer);
		}

		fclose($fhandle);
		fclose($oldFhandle);

		rename($path.'._temp', $path);
	}

	public static function createDir($path){
		if($path[0]==='{') self::realpath($path);
		
	    if(!is_dir(dirname($path)))
	        mkdir(dirname($path).'/', 0777, TRUE);
	}

	public static function put($path, $value){
		if($path[0]==='{') self::realpath($path);
		
	    if(!is_dir(dirname($path)))
	        mkdir(dirname($path).'/', 0777, TRUE);
		file_put_contents($path, $value);
	}

	public static function search($path, $regex, $recursive=false){
		if($path[0]==='{') self::realpath($path);
		
	    $dirIte = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
	    if($recursive)
	    	$dirIte = new RecursiveIteratorIterator($dirIte, RecursiveIteratorIterator::SELF_FIRST);
	    $found = new RegexIterator($recIte, $regex, RegexIterator::GET_MATCH);
	    return array_keys(iterator_to_array($found));
	}

	public static function lastModified($path){
		if($path[0]==='{') self::realpath($path);
		
		return filemtime($path);
	}

	public static function copy($path, $to){
		if($path[0]==='{') self::realpath($path);
		if($to[0]==='{') self::realpath($to);
		
		copy($path, $to);
	}

	// Can be used for moving file/folder
	public static function rename($path, $to){
		if($path[0]==='{') self::realpath($path);
		if($to[0]==='{') self::realpath($to);
		
		rename($path, $to);
	}
	public static function move($path, $to){
		if($path[0]==='{') self::realpath($path);
		if($to[0]==='{') self::realpath($to);
		
		rename($path, $to);
	}

	public static function delete($path, $recursive = false, $pathRemove = true){
		if($path[0]==='{') self::realpath($path);
		
		if(is_dir($path)){
			if(!$recursive)
				rmdir($path);
			else {
				$iterator = new DirectoryIterator($path);
				foreach($iterator as $fileinfo){
					if($fileinfo->isDot()) continue;
					if($fileinfo->isDir()){
						self::delete($fileinfo->getPathname(), true, false);
						@rmdir($fileinfo->getPathname());
					}
					if($fileinfo->isFile())
						@unlink($fileinfo->getPathname());
				}
				if($pathRemove) rmdir($path);
			}
		}
		else unlink($path);
	}

	public static function &read($path, $ranges, $readChar = false){
		if($path[0]==='{') self::realpath($path);
		
		$handle = fopen('somefile.txt', 'r');
		$temp = [];
		if($handle){
			$i = 0;
			$rangeIndex = 0;
			$rangeStart = $ranges[0][0];
			$rangeEnd = $ranges[0][1];

			// Because it's using looping, so it's separated
			// Fold this block if you see this
				if($readChar){
					while(($get = fgetc($handle)) !== false){
					    $i++;
					    if($i >= $rangeStart && $i <= $rangeEnd){
					    	$temp[] = $get;
					    	if($i === $rangeEnd){
					    		$rangeIndex++;

					    		if(!isset($ranges[$rangeIndex]))
					    			break;

								$rangeStart = $ranges[$rangeIndex][0];
								$rangeEnd = $ranges[$rangeIndex][1];
					    	}
					    }
					}
				}
				else{
					while(($get = fgets($handle)) !== false){
					    $i++;
					    if($i >= $rangeStart && $i <= $rangeEnd){
					    	$temp[] = $get;
					    	if($i === $rangeEnd){
					    		$rangeIndex++;

					    		if(!isset($ranges[$rangeIndex]))
					    			break;

								$rangeStart = $ranges[$rangeIndex][0];
								$rangeEnd = $ranges[$rangeIndex][1];
					    	}
					    }
					}
				}
		    fclose($handle);
		}
		return $temp;
	}

	public static function &tail($path, $lines = 1) {
		if($path[0]==='{') self::realpath($path);
		
		$f = @fopen($path, "rb");
		if ($f === false) return false;

		// This gives a performance boost when reading a few lines from the file.
		$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);
		if(fread($f, 1) != "\n") $lines--;
		
		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Jump backwards
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		
		fclose($f);
		return trim($output);
	}

	public static function zipDirectory($sourcePath, $outZipPath, $password='', $regex='')
	{
		if($sourcePath[0]==='{') self::realpath($sourcePath);
		if($outZipPath[0]==='{') self::realpath($outZipPath);
		
		$zip = new ZipArchive();
		if($zip->open($outZipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE))
			return false;
		
		$pathInfo = pathInfo(realpath($sourcePath));
		$dirName = $pathInfo['basename'];
		$zip->addEmptyDir($dirName);

		$fileList = self::search($sourcePath, $regex, true);
		$currentDir = $dirName;
		foreach ($fileList as &$value) {
			if(is_dir($value)){
				$currentDir = str_replace($dirName, '', $value);
				$zipFile->addEmptyDir($currentDir);
			}
			else $zipFile->addFile($value, $currentDir);
		}
		$zip->close();
		return true;
	}
	
	public static function extractZip($path, $to, $password='')
	{
		if($path[0]==='{') self::realpath($path);
		if($to[0]==='{') self::realpath($to);
		
		$zip = new ZipArchive();
		$zipStatus = $zip->open($path);
		
		if ($zipStatus === true){
			if($password !== ''){
				$zip->setPassword($password);

				if(!$zip->extractTo($to))
					return false;
			}

			elseif(!$zip->extractTo($to))
				return false;
			
			$zip->close();
		}
		else return false;
		return true;
	}
	
	public static function zipStatus($path)
	{
		if($path[0]==='{') self::realpath($path);
		
		$zip = new ZipArchive();
		$res = $zip->open(realpath($path), ZipArchive::CHECKCONS);
		if ($res !== true) {
			if($res === ZipArchive::ER_NOZIP)
				return [false, "Not a zip file"];
			if($res === ZipArchive::ER_INCONS )
				return [false, "Consistency check failed"];
			if($res === ZipArchive::ER_CRC )
				return [false, "Checksum failed"];
			else
				return [false, $res];
		}
		else return [true];
	}
}
LocalFile::init();


/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
| fopen/fread/fseek: slower, more control, less memory
| file_get_contents: faster, no control, more memory
|
*/