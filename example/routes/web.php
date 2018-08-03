<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is you can register web routes for your application.
|
*/

Route::get('/', function(){
    return view('welcome');
});

Route::get('/text', function(){
    return 'Hello, world';
});

Route::get('/hello/{message?}', function($message = null){
    if($message !== null)
        $message = 'world';

    return view('hello', ['message' => 'Hello, '.$message]);
});

Route::get('/test', function(){
	Route::get('/hello/{message?}', 'Self route');
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
}, 'auth:api');