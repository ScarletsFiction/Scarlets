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
    /*
    | Redis Connection are managed by PHPRedis Extension on your server.
    | Make sure you have installed it on your PHP.
    */
    'test1' => [
        'driver' => 'redis',
        'host' => '127.0.0.1',
        'password' => '',
        'post' => 6379,
        'database' => 0
    ],
],

];