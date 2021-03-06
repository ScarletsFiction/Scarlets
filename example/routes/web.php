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
use \Scarlets\Library\Cache;
use \Scarlets\Library\Language;
use \App\Http\Controllers\YourController;
use \Scarlets\Library\FileSystem\RemoteFile;

Route::get('/', function(){
    Serve::view('static.header', [
        'title'=>'Home'
    ]);
	Serve::view('home', [
		'time' => Language::get('time.current_date', ['date'=>date('d M Y')])
	]);
    Serve::raw('<p class="time">Server time: '.date('H:i:s').'</p>');
    Serve::view('static.footer');
});

// regex: [A-Za-z]+
Route::get('/text/{0:[A-Za-z]+}', function($text = ['world']){
    // Serve::raw("Hello, $text[0]");
    return "Hello, $text[0]";
}, 'name:text');


Route::namespaces('App\Http\Controllers', function(){
    // Class controller at "App\Http\Controllers" Namespace

    // Call 'route' function on "User\Home" Class
    Route::get('/user/{0}', 'User\Home::route');
});

Route::domain('{0}.framework.test', function($domain){
    Route::get('/home/{0}', function($query){
        // Will be available on "*.framework.test" domain
    });
});

Route::prefix('admin', function(){
    Route::get('users', function(){
        // Matches The "/admin/users" URL
        Serve::raw("Hi admin!");

        // Or route to User List
        // Route::route('list.users');
    });
});

Route::name('list', function(){
    Route::get('users', function(){
        // Route assigned name "list.users"
        Serve::raw("User List");
    }, 'name:users');
});

// Register Middleware
Route\Middleware::$register['limit'] = function($request = 2, $seconds = 30){
    // Edit from /config/cache.php -> storage -> app
    $cache = Cache::connect('app');
    $total = $cache->get('request.limit', 0);

    if($total < $request){
        // Set expiration when it's the first request only ($total == 0)
        $expire = $total === 0 ? $seconds : 0;

        // Put the request count on cache
        $cache->set('request.limit', $total + 1, $expire);
    }

    // Block request
    else{
        Serve::status(403);
        Serve::end("Forbidden (Limit reached)");
    }
};

// Limit to 2 request per 60 seconds
Route::middleware('limit:2,60', function(){
    Route::get('limit', function(){
        Serve::raw("Still accessible");
    });
});

Route::get('unlimit', function(){
    $cache = Cache::connect('app');
    $cache->set('request.limit', 0);
    Serve::raw("Limit cleared");
});

RemoteFile::listen('incoming', 'put', function($path){
    // ... Record to MySQL maybe?
});
RemoteFile::route('/remote-handler', 'incoming');