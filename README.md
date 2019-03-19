<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>

[![Written by](https://img.shields.io/badge/Written%20by-ScarletsFiction-%231e87ff.svg)](https://github.com/ScarletsFiction/)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://api.travis-ci.org/ScarletsFiction/Scarlets.svg?branch=master)](https://travis-ci.org/ScarletsFiction/Scarlets)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=Scarlets%20is%20a%20web%20framework%20for%20PHP%20that%20can%20help%20you%20build%20a%20website%20with%20API%20and%20another%20build-in%20system.%20This%20framework%20does%20a%20lazyload%20of%20it's%20system%20to%20keep%20your%20website%20in%20a%20high%20performance%20state&url=https://github.com/ScarletsFiction/Scarlets&via=github&hashtags=scarlets,framework,php)

# Scarlets
> This framework still under development

Scarlets is a web framework for PHP that can help you build a website with API and another build-in system. This framework have a lazyload on it's system, so you can select which system that you want to use to keep your website in a high performance state.

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

### Setup your custom website domain
Before we started, we need to setup Apache or Nginx to route every HTTP request into `project/public/` directory.
 - On Apache, you could setup [VirtualHost](https://gist.github.com/hoandang/8066175).
 - On Nginx, you will need to add [new site configuration](http://blog.manugarri.com/how-to-easily-set-up-subdomain-routing-in-nginx/).

If you're using Windows, you can use [Laragon](https://laragon.org/) to easily `Switch Document Root` that will automatically create new Apache VirtualHost and modify `drivers\etc\hosts` for you. So you can easily access your project with a custom domain.

## Scarlets Console

This framework has a build-in server by calling
> $ php scarlets serve (port) (address) (options)<br><br>
> Address: localhost, network, IPAddress<br>
> Options: --log, --verbose<br>

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/serve_command.webp)

Even the build-in server was blazingly fast, it still have some problem because it's running in a single thread for every request. So it's very recommended to setup your website using Nginx. But if you want to deploy a small server into Raspberry PI, Android, or other linux devices it may be better to use the build-in server.

You can also create your own command for your project

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/interactive_console.webp)

The user defined command are editable on `/routes/console.php`<br>

### Define command
> Console::command(pattern, callback, info='');
```php
Console::command('echo {*}', function($message){
    echo($message);
});
```

### Use invisible writting
> Console::hiddenInput();
```php
Console::command('input', function(){
    echo("Type something invisible: ");
    print_r("Result: ".Console::hiddenInput());
}, 'Type something');
```

### Change text color
> Console::chalk(text, color);
```php
Console::command('echo {*}', function($message){
    echo(Console::chalk("Computer> $message", 'yellow'));
});
```

### Match more pattern
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

### Adding help section
> Console::help(text, color);
```php
Console::help('echo', "Type 'echo (anything here)' to get echo back.");
```

### Clear console
> Console::clear();

### Check if running on console
> Console::isConsole();

### Obtain registered console command
> Console::collection();

### Arrange result as a table on console
> Console::table(array);

## WebRouter
This webrouter will help you route any URL Request<br>
All route should be placed on `/routes/` folder
The router priority is:
1. status.php (HTTP status)
2. api.php (API Router)
3. web.php (Web Router)

### Route by Request Method
> Route::get(pattern, callback, options='');
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

### Serve views
The views folder are located on `/resources/views/` folder
> Serve::view(file, parameter);

```php
Route::get('/', function(){
    Serve::view('hello', ['message' => 'World']);
}, 'name:home');
```

You can easily access the `message` parameter from target views like below
```php
<p class="msg"> <?= $message ?> </p>
```

or maybe obtaining `POST, GET` request with
```php
<p class="msg"> <?= $p['anything'] ?> </p>
<p class="msg"> <?= $q::post('anything') ?> </p>
```

If you're using frontend MVW framework, you can obtain dynamic view only by set `isStatic` to true for static view.

### Namespace Router group
> Route::namespace(namespace, callback);

```php
Route::namespaces('App\Http\Controllers', function(){
    // Class controller at "App\Http\Controllers" Namespace

    // Call 'route' function on "User\Home" Class
    Route::get('/user/{0}', 'User\Home::route');
});
```

### URL Prefix group
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

### Router name group
> Route::name(name, callback);

```php
Route::name('list', function(){
    Route::get('users', function(){
        // Route assigned name "list.users"
        Serve::raw("User List");
    }, 'name:users');
});
```

### Domain Router
> Route::domain(domain, callback);

```php
Route::domain('{0}.framework.test', function($domain){
    Route::get('/home/{0}', function($query){
        // Will be available on "*.framework.test" domain
    });
});
```

### Middleware Router group
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

### Registering middleware for router
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

## SQL Database
Before using this library, you must modify the database configuration on `/config/database.php`. Usage is almost similar with [SFDatabase-js](https://github.com/ScarletsFiction/SFDatabase-js).

### Get database connection
> $myDatabase = Scarlets\Library\Database::connect(databaseName='{default}');
> $myDatabase->connection // PDO Class
> $myDatabase->debug = 'log' // Log to error.log
> $myDatabase->lastQuery // Will have value if debug === true

### Transaction
Start a database transaction
```php
$myDatabase->transaction(function($db){
    $db->select(...);
    $db->insert(...);
    ...
    return true; // Rollback if set to true
});
```

### onTableMissing
Do an action if an table was missing
```php
$myDatabase->onTableMissing('users', function(){
    $myDatabase->createTable(...);
});
```

### For every parameter with `$where`
| Options  | Details |
| --- | --- |
| ! | Not Equal to |
| ~ | Like |
| !~ | Not Like |
| &~ | Like (With AND) |
| !&~ | Not Like (With AND) |
| > | More than |
| >= | More than or Equal |
| < | Less than |
| <= | Less than or Equal |
| ARRAY | (special use cases) Match number in comma separated list |
| LENGTH(<, >, <=, >=) | Return row that have some text length in the column |
| REGEXP | Use regex search `['name[REGEXP]'=>'alex|jason|loki']` |
| LIMIT | Limit returned value `['LIMIT'=>1]` or `['LIMIT'=>[$page, $length]]` |
| ORDER | Order rows based on column `['ORDER'=>['time'=>'DESC']]` |
| AND | And condition `['AND'=>['name'=>'alex', 'age'=>[34, 29]]]` |
| OR | Or condition `['OR'=>['AND'=>['type'=>'human', 'name'=>'alex']], 'type'=>'animal']` |

### Select table rows
> $myDatabase->select(tableName, $columns, $where=[]);

```php
$myDatabase->select('test', ['name', 'data'], {
    'OR'=>['id'=>123, 'words[~]'=>'hello'],
    'LIMIT'=>1
});
// SELECT name, data FROM test WHERE (id = ? OR (words LIKE ?)) LIMIT 1

/// hashtag column value: ,2,3,6,7,8,
/// This query will match above row because it's have ",3," 
$myDatabase->select('test', ['name', 'data'], {
    'hashtag[array]'=>[3,4]
});
```

### Count Matching Rows
Count rows where the data was matched by query
> $integer = $myDatabase->count($tableName, $where=[]);

### Get single row
Get a single row where the data was matched
> $data = $myDatabase->get($tableName, $column='*', $where=[]);

If `$column` was defined with *string*, it will return string of that column data. But if it's defined with *array*, it will return associative array.

### Check if table has matched row
> $boolean = $myDatabase->has($tableName, $where);

### Check if column has missing index
Find missing index from 1 to rows length and return array of number. If the `$offset` is out of bound this will return false.
> $list = $myDatabase->holes($tableName, $column, $length = 0, $offset = 0);

### Predict/Suggestion search
Predict possible similar text on a column and return percentage while the highest percentage are on first index.
> $array = $myDatabase->predict($tableName, $id = 'id', $where, &$cache);

```php
$cache = null; // This will greatly improve performance on Interactive CLI
$scores = $myDatabase->predict('users', 'user_id', ['username[%]'=>'anything'], $cache);

/* Return: Array
 *    id       Score
    [2006] => 75.4323%
    [1009] => 66.6666%
    [49]   => 60%
    [5218] => 57.2574%
    [71]   => 54%
*/
```

For better performance, the `$id` should be the `row_id`, `Primary` key, or `Unique` key. After you got the scores, you can obtain another data from the database by it's ID that returned after the prediction.

```php
$ids = array_keys($scores);
$data = $neko->select('users', ['user_id', 'username'], ['user_id'=>$ids]);

// Sort the received data from database
Scarlets\Extend\Arrays::sortWithReference($data, 'user_id', $ids);
// Do something with $data
```

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/predict_database.png)

### Insert row
Insert row to table
> $myDatabase->insert($tableName, $object, $getInsertID = false);

```php
$primary_id = $myDatabase->insert('users', [
    'name'=>'Alex Andreas',
    'username'=>'alexan',
    ...
], true);
```

Bulk insert is available when you put indexed array into `$object` parameter.

### Update row
Update some matched row in a table 
> $myDatabase->update($tableName, $object, $where = false);

```php
$myDatabase->update('posts', [
    'name[replace]'=>['Water', 'Fire'], // from 'Clean the water' to 'Clean the Fire'
    'author'=>'Brian', // from 'any' to 'Brian'
    ...
], ['LIMIT'=>1]);
```

### Addional option when updating row
| Options  | Details |
| --- | --- |
| replace | Replace needle with text |
| wrap | Wrap text between text `['name[wrap]'=>['maria', 'william']]` will result `maria ... william` |
| append | Append text |
| prepend | Prepend text |
| * / + - % | Do a math equation `['counter[+]'=>1]` |
| array-add | (special use cases) add number into a list separated by comma |
| array-remove | (special use cases) remove a number from list separated by comma |

### Delete row
Delete row from table where some condition are true. If `$where` is set to false, this will truncate the table itself.
> $myDatabase->delete($tableName, $where = false);

### Drop table
Drop a table
> $myDatabase->drop($tableName);

The other database library documentation is almost similar with [SFDatabase-js](https://github.com/ScarletsFiction/SFDatabase-js)

## Below are the undocumented library
The library documentation still in progress<br>
If you're willing to help write this documentation I will gladly accept it<br>

## LocalFile system
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\FileSystem\LocalFile;

The filesystem configuration are stored on `/config/filesystem.php`.<br>
Then you can pass `{the storage name}/your/path` to the LocalFile function.

### load
Load contents from file
> LocalFile::load(path);

```php
$data = LocalFile::load('{app}/data/text.inf');
```

`load, size, append, prepend, createDir, put, search, lastModified, copy, rename, move, delete, read, tail, zipDirectory, extractZip, zipStatus`

## Cache
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\Cache;

### get
> Cache::get(key, default=null);

The example below will return data from `timestamp` key<br>
but if the key was not found or expired, then it will return<br>
default value.

```php
$data = Cache::get('timestamp', 0);
```

### set
> Cache::set(key, value, seconds=0);

The example below will set `time()` on timestamp key<br>
and expired after 20 second.

```php
$data = Cache::set('timestamp', time(), 20);
```

`has, pull, forget, flush, extendTime`

## Crypto
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\Crypto;

The security configuration are stored on `/config/security.php`.<br>
The default cipher will be used if the cipher parameter was false.

This library is using `openssl` php extension.<br>
So make sure you have enabled it before use.<br>
And the available cipher are listed on [php documentation](http://php.net/manual/en/function.openssl-get-cipher-methods.php).

### encrypt
> Crypto::encrypt(text, pass=false, cipher=false, mask=false);

Set the mask parameter to true if you want to encode any symbol<br>
to text. Make sure you have change default `crypto_mask`<br>
on the configuration to improve security.

```php
$data = Cache::encrypt('hello', 'secretcode', false, false);
// Output:
// Tst2nVw4sDMB5M5jwwSPiTp+Olh4QTRhek55YnF6VFdPUURYa1ZKbHc9PQ==
```

### decrypt
> Crypto::decrypt(encryptedText, pass=false, cipher=false, mask=false);

```php
$data = Cache::decrypt('hello', 'secretcode');
```

## Language
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\Language;

The default language are configured on `/config/app.php`.<br>
And the languages files are located on `/resources/lang/`.<br>

### get
> Language::get($key, $values = [], $languageID='')

```php
$text = Language::get('time.current_date', [date('d M Y')], 'en');
```

## Socket
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\Socket;

### create
This function will open new socket server that able<br>
to process multiple request at the same time.
> Socket::create(address, port, readCallback, connectionCallback=0)

```php
Socket::create('localhost', 8000, function($socketResource, $data){
    echo("Data received:");
    print_r($data);
}, function($socketResource, $status){
    echo("Someone $status");
});
```

### simple
This function will open new socket server than able<br>
to process single request at the a time.
> Socket::simple(address, port, readCallback)

```php
Socket::simple('localhost', 8000, function($socketResource, $data){
    echo("Data received:");
    print_r($data);
});
```

### ping
> Socket::ping(domain, port=80)

## WebRequest
You may need to assign the namespace to the top of your code
> use \Scarlets\Library\WebRequest;

### loadURL
Web Request with curl extension
> WebRequest::loadURL(url, data="")

```php
$data = WebRequest::loadURL('https://www.google.com', [
    'ssl'=>false, // Skip ssl verification
    'header'=>['Some: header'],
    'post'=>['data' => 'anything'],
    /*
    'cookiefile'=>'path',
    'cookie'=>'urlencoded=data',
    'limitSize'=>10, // In KB
    'proxy'=>['ip' => '127.0.0.1', 'port' => 8000],
    'returnheader' => true // Return header only
    */
]);
```

### giveFiles
From this server to client browser
> WebRequest::giveFiles(filePath, fileName = null)

### download
From other server to local file
> WebRequest::download(from, to, type="curl")

### receiveFile
From client browser to this server
> WebRequest::receiveFile(directory, allowedExt, rename='')

Make sure you limit the allowed extension to<br>
avoid security issue
> allowedExt = ['css', 'js', 'png']

## Accessing App Configuration
### Get all configuration array reference
The sample below will return loaded configuration from `/config/` folder
> $config = Scarlets\Config::load('app');
> /* 
>   (App folder)/config/app.php -> `hostname` value
>   $config['app.hostname'] = 'localhost';
> */

## Debugging
### Error warning
> Scarlets\Error::warning('Something happen');

### Log
> Scarlets\Log::message('Something happen');

### Trace
> Scarlets\Log::trace('Something happen');

### Register shutdown callback
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