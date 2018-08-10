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
        'root' => 'storage/app',
    ],
    'framework' => [
        'driver' => 'localfile',
        'root' => 'storage/framework',
    ],
],

];