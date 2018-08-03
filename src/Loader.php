<?php 

/*
---------------------------------------------------------------------------
| Scarlets Loader
---------------------------------------------------------------------------
|
| This script will load required namespace library.
| Enabling opcache on your server will improve this performance.
|
*/


include_once "Config.php";
include_once "Error.php";
include_once "Cache.php";
include_once "Library.php";

/*
---------------------------------------------------------------------------
| Micro-optimization
---------------------------------------------------------------------------
|
| Write less dynamic class, and use namespace or static class.
| Scarlets library data can be stored on the registry.
| \Scarlets::$registry['LibrayName'] = ["data"=>"here"];
|
| To maintain code readability, you can separate some files.
|
*/