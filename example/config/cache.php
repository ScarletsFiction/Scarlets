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
| Supported Drivers: localfile, redis
|
*/
'storage' => [
    'app' => [
        'driver' => 'localfile',
        'path' => $frame['path.app'].'/storage/app/cache'
    ],

    /*
    | Redis Connection are managed by PHPRedis Extension on your server.
    | Make sure you have installed it on your PHP.
    | https://github.com/phpredis/phpredis/blob/develop/INSTALL.markdown
    */
    'sessions' => [
        'driver' => 'redis',
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'post' => 6379,
        'database' => 1
    ],
],

];