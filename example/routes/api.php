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

//Route::get('/api/user', function($request){
//    return $request->user();
//}, ['auth:api', 'throttle:60,1']);

# /api/status
Route::prefix('api')->middleware('auth:api,public', function(){
    Route::get('/status', 'App\Api\Sample::website');
});