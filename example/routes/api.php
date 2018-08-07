<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| You can also register API routes for your application.
|
*/
use \Scarlets\Route;
use \Scarlets\Route\Serve;

Route::get('/api/user', function($request){
    return $request->user();
}, ['auth:api', 'throttle:60,1']);

Route::middleware('auth:api')->group(function(){
    Route::get('/user', function(){
        //
    });
});