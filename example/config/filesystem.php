<?php

return [

/*
|--------------------------------------------------------------------------
| Default Filesystem Storage
|--------------------------------------------------------------------------
|
| Here you may specify the default filesystem storage id that should be used
| by the framework.
|
*/
'app' => 'app',
'framework' => 'framework',

/*
|--------------------------------------------------------------------------
| Default Cache Store
|--------------------------------------------------------------------------
|
| This option controls the default cache storage while using the caching library.
| Supported: "database", "file"
|
*/
'cache_storage' => 'cache',

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
    'app' => [
        'driver' => 'localfile',
        'root' => $frame['path.app.storage'].'/app',
    ],
    'framework' => [
        'driver' => 'localfile',
        'root' => $frame['path.app.storage'].'/framework',
    ],
    'cache' => [
        'driver' => 'localfile',
        'path' => $frame['path.app.storage'].'/cache',
    ]
],

];