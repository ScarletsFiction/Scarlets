<?php

/*
---------------------------------------------------------------------------
| Scarlets Framework Root Folder
---------------------------------------------------------------------------
|
| Here you need to set the relative or absolute path
| to the Scarlets Framework and be used globally for your project
|
*/
include_once __DIR__."/../../require.php";

/*
---------------------------------------------------------------------------
| Application Configuration Path
---------------------------------------------------------------------------
|
| When you're put a different config folder for your application
| you must change this path so Scarlets Framework know
| where to load the configurations.
|
*/
Scarlets\Config::Path(__DIR__."/config");