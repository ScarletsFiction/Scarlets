<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>

[![Written by](https://img.shields.io/badge/Written%20by-ScarletsFiction-%231e87ff.svg)](https://github.com/ScarletsFiction/)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://api.travis-ci.org/ScarletsFiction/Scarlets.svg?branch=master)](https://travis-ci.org/ScarletsFiction/Scarlets)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=Scarlets%20is%20a%20web%20framework%20for%20PHP%20that%20can%20help%20you%20build%20a%20website%20with%20API%20and%20another%20build-in%20system.%20This%20framework%20does%20a%20lazyload%20of%20it's%20system%20to%20keep%20your%20website%20in%20a%20high%20performance%20state&url=https://github.com/ScarletsFiction/Scarlets&via=github&hashtags=scarlets,framework,php)

# Scarlets
> This framework still under development

Scarlets is a web framework for PHP that can help you build a website with API and another build-in system. This framework have a lazyload on it's system, so you can select which system that you want to use to keep your website in a high performance state.

Scarlets have a build-in traffic monitor for any hacking activity or another security problem. And it will suggest you a security option if you have a backdoor on your system.

## Installation instruction

Clone/download this repository and put it on a folder
Then copy the example folder and edit the framework path on `root.php`

### Install by using command prompt
Make sure you have installed PHP on your computer (Windows and OSX can use [XAMPP](https://www.apachefriends.org/index.html))<br>
and make sure the php command is available on the command prompt

> $ php -v

If not, then you need to set it up on the [environment variables](https://www.youtube.com/watch?v=51IlfNzZVGo).

When the php command is available, open your command prompt and enter this line

> $ php -r "copy('https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/net-install', 'net-install');"<br>
> $ php net-install

The framework will automatically installed, and the example files will be prepared on your project folder.

## Upgrade
Scarlets have internal upgrade feature
> $ php scarlets upgrade

But if there are any error and the framework was unable to be loaded<br>
Please clone this repository and extract it to `vendor/scarletsfiction/scarlets`

## Usage

### Scarlets Console

This framework has a build-in server by calling
> $ php scarlets serve (port) (address) (options)<br><br>
> Address: localhost, network, IPAddress<br>
> Options: --log, --verbose<br>

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/serve_command.webp)

You can also create your own command for your project

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/interactive_console.webp)

The user defined command are editable on `/routes/console.php`<br>

#### Define command
> Console::command(pattern, callback, info='');
```php
Console::command('echo {*}', function($message){
    echo($message);
});
```

#### Use invisible writting
> Console::hiddenInput();
```php
Console::command('input', function(){
    echo("Type something invisible: ");
    print_r("Result: ".Console::hiddenInput());
}, 'Type something');
```

#### Change text color
> Console::chalk(text, color);
```php
Console::command('echo {*}', function($message){
    echo(Console::chalk("Computer> $message", 'yellow'));
});
```

#### Match more pattern
> Console::args(pattern, callback);
```php
Console::command('echo {*}', function($all){
    Console::args('{0} {1}', function($name, $message){
        echo("$name> $message");
    });

    // Check if args above was not being called
    if(!Console::$found)
        echo "Computer> $all";
});
```

#### Adding help section
> Console::help(text, color);
```php
Console::help('echo', "Type 'echo (anything here)' to get echo back.");
```

#### Clear console
> Console::clear();

#### Check if running on console
> Console::isConsole();

#### Obtain registered console command
> Console::collection();

#### Arrange result as a table on console
> Console::table(array);

### WebRouter
This webrouter will help you route any URL Request<br>
All route should be placed on `/routes/` folder
The router priority is:
1. status.php (HTTP status)
2. api.php (API Router)
3. web.php (Web Router)

#### Route by Request Method
> Console::get(pattern, callback, options='');
The accepted request method are `get`, `post`, `delete`, `put`, `options`.

The allowed match pattern are started with parameter index<br>
And followed by:
1. `:` (Regex)
2. `?` (Optional)

The parameter index for example below is `0` and the regex `[A-Za-z]+`
```php
Route::get('/text/{0:[A-Za-z]+}', function($text = ['world']){
    // Serve::raw("Hello, $text[0]");
    return "Hello, $text[0]";
});
```

#### Serve views
The views folder are located on `/resources/views/` folder
> Serve::view(file, parameter);

```php
Route::get('/', function(){
    Serve::view('hello', ['message' => 'World']);
}, 'name:home');
```

You can easily access the `message` parameter like below
> <p class="msg"> <?= $message ?> </p>

#### Serve views
The views folder are located on `/resources/views/` folder
> Serve::view(file, parameter, isStatic=false);

```php
Route::get('/', function(){
    Serve::view('hello', ['message' => 'World']);
}, 'name:home');
```

You can easily access the `message` parameter from target views like below
> <p class="msg"> <?= $message ?> </p>

or maybe obtaining `POST, GET` request with
> <p class="msg"> <?= $p['anything'] ?> </p>
> <p class="msg"> <?= $q::post('anything') ?> </p>

If you're using frontend MVW framework, you can obtain dynamic view only by set `isStatic` to true for static view.

#### Namespace Router group
> Route::namespace(namespace, callback);

```php
Route::namespaces('App\Http\Controllers', function(){
    // Class controller at "App\Http\Controllers" Namespace

    // Call 'route' function on "User\Home" Class
    Route::get('/user/{0}', 'User\Home::route');
});
```

#### URL Prefix group
> Route::prefix(prefix, callback);

```php
Route::prefix('admin', function(){
    Route::get('users', function(){
        // Matches The "/admin/users" URL
        Serve::raw("Hi admin!");

        // Or route to User List
        Route::route('list.users');
    });
});
```

#### Router name group
> Route::name(name, callback);

```php
Route::name('list', function(){
    Route::get('users', function(){
        // Route assigned name "list.users"
        Serve::raw("User List");
    }, 'name:users');
});
```

#### Domain Router
> Route::domain(domain, callback);

```php
Route::domain('{0}.framework.test', function($domain){
    Route::get('/home/{0}', function($query){
        // Will be available on "*.framework.test" domain
    });
});
```

#### Middleware Router group
> Route::domain(domain, callback);

```php
Route::middleware('limit:2,60', function(){
    Route::get('limit', function(){
        Serve::raw("Limited request");
    });
});

// Or you could also set the middleware from request method's router
Route::get('limit', function(){
    Serve::raw("Limited request");
}, 'limit:2,60');
```

#### Registering middleware for router
```php
Route\Middleware::$register['limit'] = function($request = 2, $seconds = 30){
    $total = Cache::get('request.limit', 0);

    if($total < $request){
        // Set expiration when it's the first request only ($total == 0)
        $expire = $total === 0 ? $seconds : 0;

        // Put the request count on cache
        Cache::set('request.limit', $total + 1, $expire);

        // Continue request
        return false;
    }

    // Block request
    else{
        Serve::status(404);
        return true;
    }
};
```

### Library
The library documentation still in progress<br>
If you're willing to help write this documentation I will gladly accept it

#### Database
Before using this library, you must modify the database configuration on `/config/database.php`

##### Get database connection
> $myDatabase = Scarlets\Library\Database::connect(databaseName='{default}');

##### Select table rows
> $myDatabase->select(tableName, $columns);

```php
$myDatabase->select('test', ['name', 'data'], {
    'OR'=>['id'=>321, 'words[~]'=>'hey'],
    'LIMIT'=>1
});
```

The other database library documentation is almost similar with [SFDatabase-js](https://github.com/ScarletsFiction/)

Below are undocumented library

#### LocalFile system
##### load
> Scarlets\Library\FileSystem\LocalFile::load(path);
##### size
##### append
##### prepend
##### createDir
##### put
##### search
##### lastModified
##### copy
##### rename
##### move
##### delete
##### read
##### tail
##### zipDirectory
##### extractZip
##### zipStatus

#### Cache
##### get
> Scarlets\Library\Cache::get(key, default=null);
##### set
> Scarlets\Library\Cache::set(key, value, seconds=0);
##### has
##### pull
##### forget
##### flush
##### extendTime

#### Crypto
##### encrypt
> Scarlets\Library\Crypto::encrypt(str, pass=false, cipher=false, mask=true)
##### decrypt

#### CSRF

#### Language
##### get
> Scarlets\Library\Language::get($key, $values = [], $languageID='')

#### Mailer

#### Schedule
> For Scarlets Console only

#### Socket
##### create
> Scarlets\Library\Socket::create(address, port, readCallback, connectionCallback=0)

##### simple
> Scarlets\Library\Socket::simple(address, port, readCallback)

##### ping
> Scarlets\Library\Socket::ping(domain, port=80)

#### WebRequest
##### loadURL
Web Request with curl extension
> Scarlets\Library\WebRequest::loadURL(url, data="")

##### giveFiles
From this server to client browser
> Scarlets\Library\WebRequest::giveFiles(filePath, fileName = null)

##### download
From other server to local file
> Scarlets\Library\WebRequest::download(from, to, type="curl")

##### receiveFile
From client browser to this server
> Scarlets\Library\WebRequest::receiveFile(directory, allowedExt, rename='')

### Accessing App Configuration
#### Get all configuration array reference
The sample below will return loaded configuration from `/config/` folder
> $config = Scarlets\Config::load('app');
> /* 
>   (App folder)/config/app.php -> `hostname` value
>   $config['app.hostname'] = 'localhost';
> */

### Debugging
#### Error warning
> Scarlets\Error::warning('Something happen');

#### Log
> Scarlets\Log::message('Something happen');

#### Register shutdown callback
> Scarlets::onShutdown(callback);
The callback will be called when the framework is going to shutdown

## Contribution

If you want to help in Scarlets framework, please fork this project and edit on your repository, then make a pull request to here.

Keep the code simple and clear.

## Support

If you have any question please ask on stackoverflow with tags 'scarlets-php'.<br>
But if you found bug or feature request, you post an issue on this repository.

For any private support, you can contact the author of this framework:<br>
StefansArya (Indonesia, English)<br>
stefansarya1 at gmail

## License

Scarlets is under the MIT license.

Help improve this framework by support the author ＼(≧▽≦)／