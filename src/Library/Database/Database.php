<?php
	namespace Scarlets\Library\Database;

	$connectedDB = [];

	/*
		> Initialize
		This function will return the connected database handler.
		If the database haven't connected, it will automatically
		reconnect.
	
		(id) Connection ID that configured on the application
	*/
	function init($id){
		// Check if connected
		if(isset($connectedDB[$id]))
			return $connectedDB[$id];
		else {
			// Reconnect DB
			if(!class_exists("Scarlets\Library\Database\SQL", false))
				include_once __DIR__."SQL.php";
		}
	}
