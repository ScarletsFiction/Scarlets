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
    'localfile'=>[
        'app' => $frame['path.app.storage'].'/app',

        // Required for app cache
        'cache' => $frame['path.app.storage'].'/cache',

        // Required for framework
        'framework' => $frame['path.app.storage'].'/framework'
    ]
],

];