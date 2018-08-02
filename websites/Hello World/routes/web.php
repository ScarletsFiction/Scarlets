<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello/{message?}', function ($message = null) {
    $msg = 'Example: /hello/good-bye';
    if ($message != null) {
        $msg = 'Hello, ' . $message;
    }
    // resources/views/hello.blade.php
    return view('hello', ['message' => $msg]);
});

Route::get('/world/{message?}', 'WorldController@show');

