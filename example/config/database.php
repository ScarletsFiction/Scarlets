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
    // 'scarletsfiction' is required for session manager and other
    // framework controller
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
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306', // MySQL (3306), Postgre (5432), SQLServer (1433)
        'table_prefix' => '',
        'database' => 'mydatabase',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ]
],

];