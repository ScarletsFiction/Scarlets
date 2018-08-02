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
    'default' => 'storage1',

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
        'storage1' => [
            'driver' => 'localfile',
            'root' => 'storage/app',
        ],
    ],
];
