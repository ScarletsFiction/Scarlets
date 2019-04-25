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
	private static $namespace = false;
	private static $prefix = false;
	private static $name = false;
	private static $middleware = false;
	private static $waitName = false;
	private static $skipScope = false;

	private static function implementCurrentScope(&$url, &$func, &$opts){
		if(self::$namespace && !is_callable($func))
			$func = implode('\\', self::$namespace).'\\'.$func;

		if(self::$prefix){
			if(substr($url, 0, 1) !== '/')
				$url = "/$url";

			$url = '/'.implode('/', self::$prefix).$url;

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
		private static function scopeBased($part, $arg1, $func){
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
			if(!Scarlets::$isConsole && strpos($_SERVER['REQUEST_URI'], $url) === false)
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

	public static function handleURL($url, $func, $opts, $checkOnly = false){
		if(\Scarlets::$maintenance === true)
			Serve::maintenance();

		$matched = $haveMatchAll = false;
		$args = [];
		$requestURI = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

		if(substr($url, 0, 1) !== '/')
			$url = "/$url";

		if($url === $requestURI){
			$matched = true;
			$requestURI = '';
		}

		# /text/{0}/{:}
		elseif(strpos($url, '{') !== false){
			$url = explode('{', $url);

			// Match beginning
			$temp = explode($url[0], $requestURI, 2);
			if($temp[0] !== '')
				return false;

			// Remove first match
			$requestURI = $temp[1];
			unset($url[0]);


			// Check all {param}
			foreach ($url as &$matches) {
				$matches = explode('}', $matches);

				// Replace temporary URL
				if($matches[1] !== ''){
					$current = explode($matches[1], $requestURI, 2);

					if(count($current) === 1)
						return false;

					$requestURI = $current[1];
					$current = $current[0]; // Extracted from RequestURI
				}
				else $current = $requestURI;
				
				// Find param number
				$argNumber = '';
				for ($i=0; $i < strlen($matches[0]); $i++) {
					$temp = substr($matches[0], $i, 1);
					if(!is_numeric($temp)){
						if($i === 0)
							$argNumber = false;
						break;
					}
					$argNumber .= $temp;
				}

				// Get string after param number
				$matches = substr($matches[0], $i);
				if($argNumber !== false)
					$argNumber = intval($argNumber);
				$argData = null;

				// Optional Pattern
				$optional = false;
				if(substr($matches, 0, 1) === '?'){
					$matches = substr($matches, 1);
					$optional = true;
				}

				// Regex Pattern
				if(substr($matches, 0, 1) === ':'){
					$matches = substr($matches, 1);

					if(strpos($current, '/') !== false) return false; // Strict
					if(preg_match("/$matches/", $current, $match)){
						$argData = $match;
					} else return false;
				}

				// Match after
				elseif(substr($matches, 0, 1) === '*'){
					$haveMatchAll = true;
					$argData = $current;
				}

				// Argument Match
				elseif(substr($matches, 0, 1) === '.'){
					// ToDo
				}

				// No options
				else{ // if($matches === false || $matches === ''){
					if(strpos($current, '/') !== false) return false; // Strict
					$argData = $current;
				}

				// Prepare the argument data
				if($argNumber !== false)
					$args[$argNumber] = $argData;
				else
					$args[] = $argData;

				// Check if the required param was not found
				if(!$optional && $argData === null)
					return false;
				else {
					if(is_array($argData))
						$argData = $argData[0];

					$split = explode($argData, $requestURI, 2);
					$requestURI = isset($split[1]) ? $split[1] : $split[0];
				}
			}

			$matched = true;
		}

		// Return unrecognized route
		if(!$matched) return false;
		if($checkOnly) return $matched;
		if(!$haveMatchAll && strlen($requestURI) !== 0) return false;

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