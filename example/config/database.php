<?php

return [

/*
|--------------------------------------------------------------------------
| Default Database
|--------------------------------------------------------------------------
|
| Here you may specify which database credential you wish to use as
| your default connection for your project. You can also connect to 
| another database by using database library and select the credential.
|
*/
'default' => 'yourdatabasename',

/*
|--------------------------------------------------------------------------
| Database Credentials
|--------------------------------------------------------------------------
|
| SQL Connection are managed by PDO Extension on your server.
| Make sure you have installed it on your PHP.
|
*/

'credentials' => [
    # 'scarletsfiction' is required for session manager and other
    # framework controller, or you can use Redis by changing 'session'
    # and 'auth' configuration
    'scarletsfiction' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'table_prefix' => '',
        'database' => 'scarletsfiction',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],

    'yourdatabasename' => [
        // Use same connection and credentials
        'connection' => 'scarletsfiction',

        // Use different settings for the connection
        'table_prefix' => '',
        'database' => 'anisics'
    ],

    'redis_db' => [
        'driver' => 'redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 2,
        'username' => 'root',
        'password' => '',

        // This is important for better search performance
        'indexes' => [
            'table1' => [
                // Choose your key indexes, keep it short for better performance
                // The first key will have auto_increment and unique if value is null
                'user_id', 'username', 'age', 'privilege'
            ]
        ],

        // Define your table structure and data type here
        // Available data type: number, json
        // Other than that (or not defined) will be evaluated as string
        'structure' => [
            'table1' => [
                'user_id'=>'number',
                'age'=>'number'
            ]
        ]
    ]
],

];