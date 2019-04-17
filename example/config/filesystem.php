<?php
return [
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
    // Required for app
    'app' => [
    	'driver' => 'localfile',
    	'path' => $frame['path.app'].'/storage/app'
   	],

    // Required for framework
    'framework' => [
    	'driver' => 'localfile',
    	'path' => $frame['path.app'].'/storage/framework'
    ],

    // If you want to use remote file driver (Client)
    'remote' => [
        'driver' => 'remotefile',
        'host' => 'http://target.host/remote-handler',
        'key' => 'IU.;>:G87&# 2Y$)*G)D*T&$)8 UWG(T#Gb '
    ],

    // Then this must be configured in the target server
    'incoming' => [
        'driver' => 'remotefile',
        'storage' => 'app',
        'key' => 'IU.;>:G87&# 2Y$)*G)D*T&$)8 UWG(T#Gb ',

        // Addional security
        // 'allow-ip' => ['127.0.0.1', '192.168.1.1']
    ]
],
];