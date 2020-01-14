<?php
namespace Scarlets\Extend;

class MimeType{
	public static $mimeTypes = [
		'audio/aac' => ['aac'],
		'application/x-abiword' => ['abw'],
		'application/x-freearc' => ['arc'],
		'video/x-msvideo' => ['avi'],
		'application/vnd.amazon.ebook' => ['azw'],
		'application/octet-stream' => ['bin', 'exe'],
		'image/bmp' => ['bmp'],
		'application/x-bzip' => ['bz'],
		'application/x-bzip2' => ['bz2'],
		'application/x-csh' => ['csh'],
		'text/css' => ['css'],
		'text/csv' => ['csv'],
		'application/msword' => ['doc'],
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
		'application/vnd.ms-fontobject' => ['eot'],
		'application/epub+zip' => ['epub'],
		'application/gzip' => ['gz'],
		'image/gif' => ['gif'],
		'text/html' => ['html','htm'],
		'image/vnd.microsoft.icon' => ['ico'],
		'text/calendar' => ['ics'],
		'application/java-archive' => ['jar'],
		'image/jpeg' => ['jpg','jpeg'],
		'text/javascript' => ['js'],
		'application/json' => ['json'],
		'application/ld+json' => ['jsonld'],
		'audio/midi' => ['midi','mid'],
		'audio/x-midi' => ['midi','mid'],
		'text/javascript' => ['mjs'],
		'audio/mpeg' => ['mp3'],
		'video/mpeg' => ['mpeg'],
		'application/vnd.apple.installer+xml' => ['mpkg'],
		'application/vnd.oasis.opendocument.presentation' => ['odp'],
		'application/vnd.oasis.opendocument.spreadsheet' => ['ods'],
		'application/vnd.oasis.opendocument.text' => ['odt'],
		'audio/ogg' => ['oga'],
		'video/ogg' => ['ogv'],
		'application/ogg' => ['ogx'],
		'audio/opus' => ['opus'],
		'font/otf' => ['otf'],
		'image/png' => ['png'],
		'application/pdf' => ['pdf'],
		'application/php' => ['php'],
		'application/vnd.ms-powerpoint' => ['ppt'],
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
		'application/x-rar-compressed' => ['rar'],
		'application/rtf' => ['rtf'],
		'application/x-sh' => ['sh'],
		'image/svg+xml' => ['svg'],
		'application/x-shockwave-flash' => ['swf'],
		'application/x-tar' => ['tar'],
		'image/tiff' => ['tiff','tif'],
		'video/mp2t' => ['ts'],
		'font/ttf' => ['ttf'],
		'text/plain' => ['txt'],
		'application/vnd.visio' => ['vsd'],
		'audio/wav' => ['wav'],
		'audio/webm' => ['weba'],
		'video/webm' => ['webm'],
		'image/webp' => ['webp'],
		'font/woff' => ['woff'],
		'font/woff2' => ['woff2'],
		'application/xhtml+xml' => ['xhtml'],
		'application/vnd.ms-excel' => ['xls'],
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
		'application/xml' => ['xml'],
		'text/xml' => ['xml'],
		'application/vnd.mozilla.xul+xml' => ['xul'],
		'application/zip' => ['zip'],
		'audio/3gpp' => ['3gp'],
		'video/3gpp' => ['3gp'],
		'audio/3gpp2' => ['3g2'],
		'video/3gpp2' => ['3g2'],
		'application/x-7z-compressed' => ['7z'],
	];

	public static function getMimeType($extension){
		foreach (self::$mimeTypes as $mime => &$ext) {
			if(in_array($extension, $ext, true))
				return $mime;
		}

		return 'application/octet-stream';
	}

	public static function getExtension($mimeType, $returnArray=false){
		if(!isset(self::$mimeTypes[$mimeType]))
			return 'bin';

		return $returnArray ? self::$mimeTypes[$mimeType] : self::$mimeTypes[$mimeType][0];
	}

	public static function compare($mimeType, $extension){
    	if(is_array($mimeType) && is_array($extension))
    		throw \Exception("Only single parameter allowed to be an array");

    	if(is_array($mimeType)){
    		foreach ($extension as &$val) {
    			$ext = &self::$mimeTypes[$val];

    			if(in_array($ext, $val, true))
    				return true;
    		}

    		return false;
    	}

    	if(is_array($extension)){
   			if(count(array_intersect($extension, self::$mimeTypes[$mimeType])) !== 0)
   				return true;

    		return false;
    	}
    	
    	throw \Exception("One parameter need to be an array");
    }
}