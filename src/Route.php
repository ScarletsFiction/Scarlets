<?php 
namespace Scarlets;
use Scarlets;
use Scarlets\Route\Serve;
use Scarlets\Route\Middleware;
include_once __DIR__.'/Internal/Route.php';

/*
---------------------------------------------------------------------------
| Scarlets Route
---------------------------------------------------------------------------
|
| Description haven't added
|
*/
class Route{
	public static $instantOutput = false;
	public static $statusCode = 404;
	public static $this = false;
	public static $ended = false;
	public static $_currentURL = '';
	private static $namespace = false;
	private static $prefix = false;
	private static $name = false;
	private static $middleware = false;
	private static $waitName = false;
	private static $skipScope = false;

	private static function implementCurrentScope(&$url, &$func, &$opts){
		if(self::$namespace && !is_callable($func))
			$func = implode('\\', self::$namespace).'\\'.$func; // ToDo: Improve performance

		if(self::$prefix){
			if(substr($url, 0, 1) !== '/')
				$url = "/$url";

			$url = '/'.implode('/', self::$prefix).$url; // ToDo: Improve performance

			if(strpos($url, '//') !== false)
				trigger_error("The route can't have double slash \"$url\"");
		}

		if(self::$name && $opts !== false){
			if(!is_array($opts))
				$opts = [$opts];

			foreach($opts as &$value){
				if(strpos($value, 'name:') !== false){
					$name = implode('.', self::$name).'.'.substr($value, 5);

					if(self::$waitName !== false){
						foreach (self::$waitName as &$wait) {
							if($wait[0] !== $name)
								continue;

							call_user_func_array($func, $wait[1]);

							unset($wait);
							if(count(self::$waitName) === 0)
								self::$waitName = false;
						}
					}

					$value = "name:$name";
				}
			}
		}

		if(self::$middleware){
			if(is_array($opts))
				$opts = array_merge(self::$middleware, $opts);
			else
				$opts = self::$middleware;
		}
	}

	// ---- Route by request method ----
		private static function method($method, &$url, &$func, &$opts){
			if(self::$namespace || self::$prefix || self::$name || self::$middleware)
				self::implementCurrentScope($url, $func, $opts);

			if(Scarlets::$isConsole)
				Route\Handler::register($method, $url, $func, $opts);

			elseif($_SERVER['REQUEST_METHOD'] === $method && self::handleURL($url, $func, $opts))
				exit;
		}

		public static function get($url, $func, $opts = false){
			self::method('GET', $url, $func, $opts);
		}
		
		public static function post($url, $func, $opts = false){
			self::method('POST', $url, $func, $opts);
		}
		
		public static function delete($url, $func, $opts = false){
			self::method('DELETE', $url, $func, $opts);
		}
		
		public static function put($url, $func, $opts = false){
			self::method('PUT', $url, $func, $opts);
		}
		
		public static function options($url, $func, $opts = false){
			self::method('OPTIONS', $url, $func, $opts);
		}
		
		public static function any($url, $func, $opts = false){
			if(self::$namespace || self::$prefix || self::$name || self::$middleware)
				self::implementCurrentScope($url, $func, $opts);
			
			if(Scarlets::$isConsole)
				Route\Handler::register('ANY', $url, $func, $opts);

			elseif(self::handleURL($url, $func, $opts))
				return true;
		}
		
		public static function match($methods, $url, $func, $opts = false){
			if(self::$namespace || self::$prefix || self::$name || self::$middleware)
				self::implementCurrentScope($url, $func, $opts);
			
			foreach ($methods as $method) {
				$method = strtoupper($method);
				if(Scarlets::$isConsole)
					Route\Handler::register($method, $url, $func, $opts);

				elseif($_SERVER['REQUEST_METHOD'] === $method && self::handleURL($url, $func, $opts))
					return true;
			}
		}
	
	public static function domain($domain, $func){
		if(Scarlets::$isConsole)
			return;
		
		if(strpos($domain, '{') === false){
			if($domain === $_SERVER['HTTP_HOST'])
				$func();
			return;
		}

		$args = [];
		$currentDomain = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
		$domain = array_reverse(explode('.', $domain));
		if(count($currentDomain) === count($domain)){
			$i = -1;
			foreach ($domain as &$value) {
				$i++;
				if(strpos($value, '{') === false){
					if($value !== $currentDomain[$i])
						return false;
					continue;
				}

				$found = $currentDomain[$i];

				// Check beginning string
				$value = explode('{', $value);
				if($value[0] !== ''){
					if(strpos($found, $value[0]) === 0)
						$found = substr($found, strlen($value[0]));
					else return false;
				}
				$value = $value[1];

				// Check ending string
				$value = explode('}', $value);
				if($value[1] !== ''){
					$delta = strlen($found) - strlen($value[1]);
					if(strpos($found, $value[1]) - $delta === 0)
						$found = substr($found, 0, $delta);
					else return false;
				}
				$value = $value[0];

				// Find param number
				$argNumber = '';
				for ($i=0; $i < strlen($value[0]); $i++) {
					$temp = substr($value[0], $i, 1);
					if(!is_numeric($temp)){
						if($i === 0)
							$argNumber = false;
						break;
					}
					$argNumber .= $temp;
				}

				$args[intval($argNumber)] = $found;
			}
			call_user_func_array($func, $args);
		}
	}

	public static function route($name, $data = []){
		if(self::$waitName !== false)
			self::$waitName = [];

		$ref = &Scarlets::$registry['Route']['NAME'];
		if(isset($ref[$name]))
			return call_user_func_array($ref[$name], $data);

		self::$waitName[] = [$name, $data];
	}

	public static function status($code, $func){
		Route\Handler::register('STATUS', $code, $func);
	}
	
	// ---- Temporary scope based function ----
		private static $scopeConstrain = [];
		private static $constrainLength = [];
		private static function scopeBased($part, $arg1, $func){ // ToDo: Improve performance
			$current = &self::${$part};
			if($current === false)
				$current = [];

			if(is_array($arg1))
				$current = array_merge($current, $arg1);
			else
				$current[] = $arg1;

			self::$scopeConstrain[] = $part;
			if(!$func)
				return self::$this;

			self::$constrainLength[] = count(self::$scopeConstrain);

			if(!self::$skipScope)
				$func();
			else self::$skipScope = false;

			if(is_array($current)){
				array_pop($current);
				if(count($current) === 0)
					$current = false;
			}

			// Clear last constrain
			if(count(self::$constrainLength) >= 2) // Get delta of newest constraint length with the older length
				$delete = array_pop(self::$constrainLength) - self::$constrainLength[count(self::$constrainLength) - 1];
			else $delete = array_pop(self::$constrainLength);

			$skipOnce = false; // Because when $func is defined, the scope will removed
			for ($i = $delete - 1; $i >= 0; $i--){
				$temp = array_pop(self::$scopeConstrain);
				if($skipOnce === false){
					$skipOnce = true;
					continue;
				}
				$ref = &self::${$temp};

				$count = count($ref);
				if($count !== 0){
					array_pop($ref);
					$count--;
				}
				if($count === 0)
					$ref = false;
			}
		}

		public static function namespaces($namespace, $func = false){
			return self::scopeBased('namespace', $namespace, $func);
		}

		public static function prefix($url, $func = false){
			// Execute only if there are a matched url
			if(!Scarlets::$isConsole && strpos(self::$_currentURL, $url) === false)
				self::$skipScope = true;

			return self::scopeBased('prefix', $url, $func);
		}

		public static function name($name, $func = false){
			return self::scopeBased('name', $name, $func);
		}

		public static function middleware($controller, $func = false){
			return self::scopeBased('middleware', $controller, $func);
		}

	private static function getCallbackType($func){
		$functionReflection = new ReflectionFunction($func);
		$parameters = $functionReflection->getParameters();

		$types = [];
		foreach($parameters as $parameter){
			$types[] = $parameter->getClass()->name;
		}
		return $types;
	}

	// Handle Template Only
	public static function template($url, $path){
		if('/'.implode('/', Route::$prefix)."$url" !== self::$_currentURL)
			return;

		if(isset($_GET['_sf_view']) === false || isset($path[$_GET['_sf_view']]) === false)
			return;

		$plate = &Scarlets::$registry['path.plate'];
		$ref = &$path[$_GET['_sf_view']];

		if(is_array($ref)){
			foreach ($ref as &$value) {
				$path = $plate.str_replace('.', '/', $value).'.php';
				Serve::raw(str_replace(["  ", "\n", "\r"], '', file_get_contents($path)));
			}

			Route::$statusCode = 200;
			Serve::end();
		}

		$path = $plate.str_replace('.', '/', $ref).'.php';
		Route::$statusCode = 200;

		Serve::end(str_replace(["  ", "\n", "\r"], '', file_get_contents($path)));
	}

	public static function handleURL($url, $func, $opts, $checkOnly = false){
		if(\Scarlets::$maintenance === true)
			Serve::maintenance();

		$args = [];
		$requestURI = self::$_currentURL;

		if(substr($url, 0, 1) !== '/')
			$url = "/$url";

		if($url === $requestURI)
			$requestURI = '';

		# /text/{0}/{:}
		elseif(strpos($url, '{') !== false){
			$args = \Scarlets\Internal\Pattern::parse($url, $requestURI, '/');

			if(!$args)
				return false;
		}

		else return false;

		// Return unrecognized route
		if($checkOnly) return true;

		// Handle controller
		if(!is_callable($func)){
			trigger_error("'$func' is not callable");
			return;
		}
		Route\Middleware::$routerArgs = &$args;

		// Handle middleware
		$middlewareCallback = false;
		if($opts !== false){
			if(!is_array($opts))
				$opts = [$opts];

			foreach($opts as $ware){
				$middlewareCallback = Route\Middleware::callMiddleware($ware);
				if($middlewareCallback === false)
					return false;
			}
		}
		
		self::$statusCode = 200;
		$pendingData = null;

		// Call callback function
		if(self::$instantOutput === false){
			ob_start();
			if(count($args) !== 0)
				$pendingData = call_user_func_array($func, $args);
			else $pendingData = $func();

			if(Scarlets\Error::$hasError === false)
				echo ob_get_contents();
			else Scarlets\Error::$hasError = false;

			ob_end_clean();
		}
		else {
			if(count($args) !== 0) $pendingData = call_user_func_array($func, $args);
			else $pendingData = call_user_func($func);
		}

		if(is_callable($middlewareCallback)){
			Middleware::$pendingData = &$pendingData;
			call_user_func_array($middlewareCallback, Middleware::$pendingArgs);
			Middleware::$pendingArgs = [];
			Middleware::$pendingData = null;
		}
		else echo $pendingData;

		return true;
	}

	/*
		> Force Secure
		Force any http request to be send from https protocol
	*/
	public static function forceSecure(){
		if(\Scarlets::$isConsole)
			return;
		
		if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off'){
		    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		    header("Location: $redirect", true);
		    exit;
		}
	}
}

// Refer to 'Route' class itself
Route::$this = new Route;
Route::$_currentURL = explode('?', $_SERVER['REQUEST_URI'], 2)[0];