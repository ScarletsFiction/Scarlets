<?php
	$start = microtime(true);

	// Control startup with Scarlets Framework
	include_once __DIR__."/../root.php";

	// You can register other autoloader here
	// include "vendor/autoload.php";

	// Use the website system
	Scarlets::Website();

	// For benchmarking
	print_r("\n<!-- Dynamic page generated in ".round(microtime(true) - $start, 5)." seconds. -->");