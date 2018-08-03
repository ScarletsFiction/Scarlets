<?php

return [

/*
|--------------------------------------------------------------------------
| Mail Driver
|--------------------------------------------------------------------------
|
| Here you can configure your Mail Driver to use internal build PHP's mail
| Or with SMTP server for sending email.
|
| Driver supported: "smtp", "phpmail", "url"
|
*/
'driver' => 'smtp',
'port' => 587,
'encryption' => 'tls',

/*
|--------------------------------------------------------------------------
| URL or SMTP Host
|--------------------------------------------------------------------------
|
| Here you may provide the host address of the SMTP server for sending email.
| You can also set it to an URL if you want to send all email to an URL.
|
| Email send to URL will be send as JSON by POST request with format:
| $_POST['mail'] = {
|    from:{
|        address:'', 
|        name:''
|    },
|    to:'',
|    message:'',
|    timestamp:123,
|    replying:{
|        to:'',
|        message:'',
|        timestamp:123
|    }
| }
|
*/
'host' => 'smtp.yahoo.com',

/*
|--------------------------------------------------------------------------
| Your Mail Address
|--------------------------------------------------------------------------
|
| Here you can specify your email address that will be sent by your 
| application. If you not specify it, then it would use the default system
| configuration. This address will be used globally if you use the internal
| mail sysyem.
|
*/
'from' => [
    'address' => 'hello@example.com',
    'name' => 'Example',
],

/*
|--------------------------------------------------------------------------
| SMTP Credential
|--------------------------------------------------------------------------
|
| This is required for authentication if you have set a username and
| password on the SMTP server.
|
*/
'username' => 'admin',
'password' => 'pass123',

];