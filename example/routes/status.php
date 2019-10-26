<?php

/*
|--------------------------------------------------------------------------
| HTTP Status
|--------------------------------------------------------------------------
|
| Here you can handle every php status if your app need to handle it
| when it's happen
|
*/
use \Scarlets\Route;
use \Scarlets\Route\Serve;

Route::status(500, function(){
    Serve::view('static.header', [
        'title'=>'Server Message'
    ]);
    Serve::view('status', [
        'status' => 'Internal Server Error',
        'information' => 'The server thrown 500 http code <br> Looks like there are some error on the server'
    ]);
    Serve::view('static.footer');
});

Route::status(503, function(){
    Serve::view('static.header', [
        'title'=>'Maintenance'
    ]);
    Serve::view('status', [
        'status' => 'Maintenance',
        'information' => 'There are some maintenance on the server'
    ]);
    Serve::view('static.footer');
});

Route::status(404, function(){
    Serve::view('static.header', [
        'title'=>'Not Found'
    ]);
    Serve::view('status', [
        'status' => '404 Not Found',
        'information' => "The request for ".$_SERVER['REQUEST_URI']." was not found"
    ]);
    Serve::view('static.footer');
});