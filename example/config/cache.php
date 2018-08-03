<?php

namespace Scarlets\Config\Cache;

/*
|--------------------------------------------------------------------------
| Default Cache Store
|--------------------------------------------------------------------------
|
| This option controls the default cache storage while using the caching library.
| Supported: "database", "file"
|
*/
const select = 'file';

/*
|--------------------------------------------------------------------------
| Cache Storage
|--------------------------------------------------------------------------
|
| Here you can define available cache storage for your application
|
*/
const storage = [
    // 'database' => [
    //     'database_id' => 'scarlets',
    //     'table' => 'cache',
    // ]

    'file' => [
        'path' => $AppPath.'framework/cache',
    ]
];