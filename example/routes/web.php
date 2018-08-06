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
		'time' => Language\get('time.current_date', [date('d M Y')])
	]);
	Serve::view('static.footer');
});

Route::get('/text/{:[A-Za-z]+}', function($text = 'world'){
	$route = Route::current();
	$name = Route::currentRouteName();
	$action = Route::currentRouteAction();
    return "Hello, $text";
}, 'name:text');

Route::get('/hello/{0}', function($message = null){
    if($message !== null)
        $message = 'world';

    return Route::view('hello', ['message' => 'Hello, '.$message]);
});

Route::get('/test', function(){
	Route::get('/hello/{0}', 'Redirect');
});

Route::namespaces('User')->group(function(){
    // Class controller at "App\Http\Controllers\User" Namespace
});

Route::domain('{0}.domain.com')->group(function(){
    Route::get('home/{0}', function($query){
        //
    });
});

Route::prefix('admin')->group(function(){
    Route::get('users', function(){
        // Matches The "/admin/users" URL
    });
});

Route::name('admin.')->group(function(){
    Route::get('users', function(){
        // Route assigned name "admin.users"...
    })->name('users');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| You can also register API routes for your application.
|
*/

Route::get('/api/user', function($request){
    return $request->user();
}, ['auth:api', 'throttle:60,1']);

Route::middleware('auth:api')->group(function(){
    Route::get('/user', function(){
        //
    });
});