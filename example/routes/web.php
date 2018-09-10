<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is you can register web routes for your application.
|
*/
use \Scarlets\Route;
use \Scarlets\Route\Serve;
use \Scarlets\Route\Query;
use \Scarlets\Library\Language;

Route::get('/', function(){
	Serve::view('static.header', [
		'title'=>'Home'
	]);
	Serve::view('home', [
		'time' => Language::get('time.current_date', [date('d M Y')])
	]);
	Serve::view('static.footer');
});

// regex: [A-Za-z]+
Route::get('/text/{0:[A-Za-z]+}', function($text = ['world']){
    // Serve::raw("Hello, $text[0]");
    return "Hello, $text[0]";
}, 'name:text');

// Optional message
Route::get('/hello/{0?}', function($message = 'world'){
    Serve::view('hello', ['message' => 'Hello, '.$message]);
});

Route::namespaces('App\Http\Controllers', function(){
    // Class controller at "App\Http\Controllers" Namespace
});

Route::domain('{0}.framework.test', function($domain){
    Route::get('/home/{0}', function($query){
        // Will be available on "*.framework.test" domain
    });
});

Route::prefix('admin', function(){
    Route::get('users', function(){
        // Matches The "/admin/users" URL
    });
});

Route::name('list', function(){
    Route::get('users', function(){
        // Route assigned name "list.users"...
    }, 'name:users');
});