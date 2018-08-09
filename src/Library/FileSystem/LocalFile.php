<?php

namespace Scarlets\Library\FileSystem;

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
	public static function load($path){
		return file_get_contents($path);
		/*
	    	$fhandle = fopen($path, "r");
	    	$fcontents = fread($fhandle, filesize($path));
	    	fclose($fhandle);
		*/
	}

	public static function size($path){
		if(!file_exists($path)) return 0;
		if(is_file($path))
			return filesize($path);
	}

	public static function append($path, $value, $firstLine = false){
		file_put_contents($path, $value, FILE_APPEND);
	}

	public static function createDir($path){
	    if(!is_dir(dirname($path)))
	        mkdir(dirname($path).'/', 0777, TRUE);
	}

	public static function put($path, $value){
	    if(!is_dir(dirname($path)))
	        mkdir(dirname($path).'/', 0777, TRUE);
		file_put_contents($path, $value);
		/*
	        $f = @fopen($path, 'w');
	        if(!$f) return false;
	        $bytes = fwrite($f, $data);
	        fclose($f);
	        return $bytes;
		*/
	}

	public static function search($path, $name, $recursive=false){
	    $dirIte = new RecursiveDirectoryIterator($path);
	    if($recursive)
	    	$dirIte = new RecursiveIteratorIterator($dirIte);
	    $found = new RegexIterator($recIte, $pattern, RegexIterator::GET_MATCH);
	    return array_keys(iterator_to_array($found));
	}

	public static function time($path){
		return filemtime($path);
	}

	public static function copy($path, $to){
		copy($path, $to);
	}

	// Can be used for moving file/folder
	public static function rename($path, $to){
		rename($path, $to);
	}

	public static function remove($path, $recursive = false, $pathRemove = true){
		if(is_dir($path)){
			if(!$recursive)
				rmdir($path);
			else {
				$iterator = new DirectoryIterator($path);
				foreach($iterator as $fileinfo){
					if($fileinfo->isDot()) continue;
					if($fileinfo->isDir()){
						self::remove($fileinfo->getPathname(), true, false);
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

	public static function &tail($filepath, $lines = 1) {
		$f = @fopen($filepath, "rb");
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
}


/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
| fopen/fread/fseek: slower, more control, less memory
| file_get_contents: faster, no control, more memory
|
*/