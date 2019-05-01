<?php

return [

/*
|--------------------------------------------------------------------------
| Session Driver
|--------------------------------------------------------------------------
|
| This option controls the default session driver that will be used on
| every requests. By default, it will use the default PHP session driver but
| you can also save the session to another places.
|
| Supported: "redis", "database"
|
*/
'driver' => 'database',

/*
|--------------------------------------------------------------------------
| Session Lifetime
|--------------------------------------------------------------------------
|
| Here you can specify the length minutes that you want the session
| to be remain idle before it expires. You can also set the cookie to
| immediately expire when user closed their browser.
|
*/
'lifetime' => 43200,
'expire_on_close' => false,

/*
|--------------------------------------------------------------------------
| Session Database Connection
|--------------------------------------------------------------------------
|
| When using the "database" session drivers, you may specify a
| credential id that should be used to save these sessions.
|
*/
'credential' => 'scarletsfiction',
'table' => 'sessions',

/*
|--------------------------------------------------------------------------
| Cookie Path
|--------------------------------------------------------------------------
|
| Set this on the root path if you want the cookie accessible from any URL
| within your domain.
|
*/
'path' => '/',

/*
|--------------------------------------------------------------------------
| Cookie Domain
|--------------------------------------------------------------------------
|
| Here you can change the cookie domain for your cookie
| (But only if you send the cookie response from that domain).
| Usually you want the cookie accessible on sub-domain, so you can use
| asterisk symbol (*.website.com). Or use the current host with (@host)
|
*/
'domain' => '.@host',

/*
|--------------------------------------------------------------------------
| Cookies for HTTPS/Secure only
|--------------------------------------------------------------------------
|
| If you set this to true, every cookie will only be send back by the browser
| when it's accessing through HTTPS Protocol only.
|
*/
'secure' => false,

/*
|--------------------------------------------------------------------------
| Cookies for HTTP Only
|--------------------------------------------------------------------------
|
| By setting this value to true, it will block JavaScript from accessing
| cookie value and cookie will only be accessible through the server.
| This is very recommended to prevent XSS attack.
*/
'http_only' => true,

];