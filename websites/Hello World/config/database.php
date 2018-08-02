<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database
    |--------------------------------------------------------------------------
    |
    | Here you may specify which database credential you wish to use as
    | your default connection for all database work. You can also
    | have many database connection by using database library.
    |
    */
    'default' => 'database1',

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
        // 'sqlite'=>[
        //     'driver' => 'sqlite'
        //     'database' => './../sqlite.db',
        // ],

        'database1' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306', // MySQL (3306), Postgre (5432), SQLServer (1433)
            'prefix' => '',
            'database' => 'name',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]
    ],
];
