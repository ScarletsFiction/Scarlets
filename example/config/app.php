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
| This hostname is used by the by internal service if there are runtime task or
| being accessed from the console.
|
*/
'hostname' => 'localhost',

/*
|--------------------------------------------------------------------------
| Application Sub URL Path
|--------------------------------------------------------------------------
|
| If you're have a sub-path for your application. you need to fill
| this with the URL path instead (without hostname and started with '/')
| If you're already handle the public folder with proxy handler,
| then leave this with false
|
*/
'url_path' => getRootURL(), // 'url_path' => false,

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
'simplify_trace' => true,

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
'log' => 'single',
'log_level' => 'debug',

/*
|--------------------------------------------------------------------------
| Instant Output Mode
|--------------------------------------------------------------------------
|
| This method can help reduce memory load by serve the instant output
| without buffering to the memory. But if there are any error after
| any ouput, the client can't be redirected to another page
| because the first header already sended with 200 HTTP code. 
|
*/
'instant' => true,

/*
|--------------------------------------------------------------------------
| Console username
|--------------------------------------------------------------------------
|
| Here you can specify your favourite name when you're using
| Scarlets Console in interactive mode. You can also change it when running
| with runtime configuration.
|
*/
'console_user' => 'You',

];

// You should remove this if you're not using the 'hello world' example
// This function obtaining the root URL when not using proxy handler
function getRootURL(){
	if(!isset($_SERVER['REQUEST_URI'])) return false;

	// Check if the app have 'public' path
	if(strpos($_SERVER['REQUEST_URI'], '/public/') !== false){
		$temp = \Scarlets::$registry['path.app'];
		
		// Replace windows directory separator
		if(DIRECTORY_SEPARATOR !== '/') $temp = str_replace('\\', '/', $temp);

		// Add 'public' path to app path
		$temp .= '/public/';

		// Get the public directory path
		$temp2 = explode('/public/', $_SERVER['REQUEST_URI']);
		if(count($temp2) !== 1 && $temp2[0] !== ''){
			$temp2 = $temp2[0].'/public';

			// Check if the public directory path was matched with app path
			// temp = D:/some/path/to/scarlets/example/public/
			// temp2 = /scarlets/example/public
			if(strpos($temp, $temp2) !== false){
				return $temp2;
			}
		}
	}

	// Otherwise it may not the app's public directory path
	return false;
}