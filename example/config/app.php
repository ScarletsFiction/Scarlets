<?php

return [

/*
|--------------------------------------------------------------------------
| Application ID
|--------------------------------------------------------------------------
|
| The AppID should have 4-8 number only.
| This will be used by scarlets to identify which app is running.
|
*/
'appid' => 'hello123',

/*
|--------------------------------------------------------------------------
| Application Hostname
|--------------------------------------------------------------------------
|
| This URL is used by the by internal service if there are runtime task or
| being accessed from the console.
|
*/
'hostname' => 'localhost',

/*
|--------------------------------------------------------------------------
| Application Timezone
|--------------------------------------------------------------------------
|
| This will be used for the PHP date and time functions.
|
*/
'timezone' => 'UTC',

/*
|--------------------------------------------------------------------------
| Application Language
|--------------------------------------------------------------------------
|
| By default scarlets will select the available language on the
| 'resources/lang' folder depends on client browser's language.
| But if the language file was not found, then it would default
| to this setting.
|
*/
'language' => 'en',

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| This key is used by the internal encrypter service and should be set
| randomly. Otherwise these encrypted strings will not be safe.
|
*/
'key' => 'MyPassword123',
'cipher' => 'AES-128-CBC',

/*
|--------------------------------------------------------------------------
| Debug Mode
|--------------------------------------------------------------------------
|
| When your application is in debug mode, some messages with
| stack traces will be shown on every error that occurs within your
| application. If disabled, the general error page will be shown.
|
*/
'debug' => true,
'warning_as_error' => false,

/*
|--------------------------------------------------------------------------
| Logging Configuration
|--------------------------------------------------------------------------
|
| Here you can configure the log settings for your application.
| 
| The available options:
| (nothing)  Output to browser only
| (single)   Output to single file
| (daily)    Output on separated days
| (syslog)   Output to default system log
| (errorlog) Output to 'error.log'
|
*/
'log' => 'nothing',
'log_level' => 'debug',

];