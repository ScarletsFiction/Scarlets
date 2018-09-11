<?php

return [

/*
|--------------------------------------------------------------------------
| Default Cache Store
|--------------------------------------------------------------------------
|
| This option controls the default cache storage while using the caching library.
|
*/
'cache_storage' => 'app',

/*
|--------------------------------------------------------------------------
| Filesystem Storages
|--------------------------------------------------------------------------
|
| Here you can to configure many filesystem storage with different driver.
|
| Supported Drivers: "localfile"
|
*/
'storage' => [
    // Required for app
    'app' => [
    	'driver' => 'localfile',
    	'path' => $frame['path.app'].'/storage/app'
   	],

    // Required for framework
    'framework' => [
    	'driver' => 'localfile',
    	'path' => $frame['path.app'].'/storage/framework'
    ]
],

];