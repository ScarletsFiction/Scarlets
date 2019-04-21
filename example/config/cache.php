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
| Cache Storages
|--------------------------------------------------------------------------
|
| Here you can to configure many cache storage with different driver.
|
| Supported Drivers: "localfile"
|
*/
'storage' => [
    'app' => [
        'driver' => 'localfile',
        'path' => $frame['path.app'].'/storage/app/cache'
    ],

    // Required by framework
    'framework' => [
        'driver' => 'localfile',
        'path' => $frame['path.app'].'/storage/framework/cache'
    ],
],

];