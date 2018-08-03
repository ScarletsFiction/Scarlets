<?php
	namespace Scarlets\Library\FileSystem;
	use Scarlets\Config\FileSystem;

	/*
		> Initialize
	
		(id) Storage ID that configured on the application
	*/
	function init($id = null){
		if(select === 'localfile'){
			if(!class_exists("Scarlets\\Library\\FileSystem\\LocalFile", false))
				include_once __DIR__."LocalFile.php";
			return true;
		}
		
	}
