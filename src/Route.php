<?php 
namespace Scarlets;
use Scarlets;
include_once __DIR__."/Internal/Route.php";

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
	public static $uri = '';
	public static $this = false;
	private static $namespace = false;
	private static $prefix = false;
	private static $name = false;
	private static $middleware = false;

	private static function implementCurrentScope(&$url, &$func, &$opts){
		if(self::$namespace && !is_callable($func))
			$func = implode('\\', self::$namespace).'\\'.$func;

		if(self::$prefix){
			if(substr($url, 0, 1) !== '/')
				$url = '/'.$url;

			$url = '/'.implode('/', self::$prefix).$url;
		}

		if(self::$name && $opts !== false){
			if(!is_array($opts)){
				if(strpos($opts, 'name:') !== false)
					$opts = 'name:'.implode('.', self::$name).'.'.substr($opts, 5);
			} else {
				foreach($opts as &$value){
					if(strpos($value, 'name:') !== false)
						$value = 'name:'.implode('.', self::$name).'.'.substr($value, 5);
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

	public static function get($url, $func, $opts = false){
		if(self::$namespace || self::$prefix || self::$name || self::$middleware)
			self::implementCurrentScope($url, $func, $opts);

		if(Scarlets::$isConsole)
			Route\Handler::register('GET', $url, $func, $opts);

		elseif($_SERVER['REQUEST_METHOD'] === 'GET' && self::handleURL($url, $func, $opts))
			return true;
	}
	
	public static function post($url, $func, $opts = false){
		if(self::$namespace || self::$prefix || self::$name || self::$middleware)
			self::implementCurrentScope($url, $func, $opts);

		if(Scarlets::$isConsole)
			Route\Handler::register('POST', $url, $func, $opts);

		elseif($_SERVER['REQUEST_METHOD'] === 'POST' && self::handleURL($url, $func, $opts))
			return true;
	}
	
	public static function delete($url, $func, $opts = false){
		if(self::$namespace || self::$prefix || self::$name || self::$middleware)
			self::implementCurrentScope($url, $func, $opts);
		
		if(Scarlets::$isConsole)
			Route\Handler::register('DELETE', $url, $func, $opts);

		elseif($_SERVER['REQUEST_METHOD'] === 'DELETE' && self::handleURL($url, $func, $opts))
			return true;
	}
	
	public static function put($url, $func, $opts = false){
		if(self::$namespace || self::$prefix || self::$name || self::$middleware)
			self::implementCurrentScope($url, $func, $opts);
		
		if(Scarlets::$isConsole)
			Route\Handler::register('PUT', $url, $func, $opts);

		elseif($_SERVER['REQUEST_METHOD'] === 'PUT' && self::handleURL($url, $func, $opts))
			return true;
	}
	
	public static function options($url, $func, $opts = false){
		if(self::$namespace || self::$prefix || self::$name || self::$middleware)
			self::implementCurrentScope($url, $func, $opts);
		
		if(Scarlets::$isConsole)
			Route\Handler::register('OPTIONS', $url, $func, $opts);

		elseif($_SERVER['REQUEST_METHOD'] === 'OPTIONS' && self::handleURL($url, $func, $opts))
			return true;
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
	
	// $http = http status like 301 or redirect method
	public static function redirect($from, $to, $http, $data = false){
		if(!self::handleURL($from, false, false, true)) return;

		if(!is_numeric($http)){
			// GET method
			if(strtolower($method) === 'get'){
				if($data)
					header('Location: '.explode('?', $URL)[0].'?'.http_build_query($data));
				else
					header('Location: '.$URL);
			}

			// POST method
			else {
				?><!DOCTYPE html><html><head></head><body>
				<form id="autoForm" action="<?php echo(sanitizeURL($url));?>" method="post">
				<?php 
					if($data) foreach ($data as $key => $value) {
						echo('<input type="hidden" name="'.sanitizeText($key).'" value="'.sanitizeText($value).'">');
					}
				?>
				</form>
				<script type="text/javascript">document.getElementById('autoForm').submit();</script></body></html><?php
			}
		} else
			header("Location: $to", true, $http);
		exit;
	}

	public static function status($code, $func){
		Route\Handler::register('STATUS', $code, $func);
	}
	
	// ---- Temporary scope based function ----
	public static function namespaces($namespace, $func){
		if(self::$namespace === false)
			self::$namespace = [];
		self::$namespace[] = $namespace;
		
		$func();

		array_pop(self::$namespace);
		if(count(self::$namespace) === 0)
			self::$namespace = false;
	}
	
	public static function prefix($url, $func){
		if(self::$prefix === false)
			self::$prefix = [];
		self::$prefix[] = $url;
		
		$func();

		array_pop(self::$prefix);
		if(count(self::$prefix) === 0)
			self::$prefix = false;
	}
	
	public static function name($name, $func){
		if(self::$name === false)
			self::$name = [];
		self::$name[] = $name;
		
		$func();

		array_pop(self::$name);
		if(count(self::$name) === 0)
			self::$name = false;
	}
	
	public static function middleware($controller, $func){
		if(self::$middleware === false)
			self::$middleware = [];
		self::$middleware[] = $controller;
		
		$func();

		array_pop(self::$middleware);
		if(count(self::$middleware) === 0)
			self::$middleware = false;
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
		$matched = false;
		$args = [];
		
		if(substr($url, 0, 1) !== '/')
			$url = '/'.$url;

		$requestURI = $_SERVER['REQUEST_URI'];
		if($url === $requestURI)
			$matched = true;

		# /text/{0}/{:}
		elseif(strpos($url, '{') !== false){
			$url = explode('{', $url);

			// Match beginning
			$temp = explode($url[0], $requestURI, 2);
			if($temp[0] !== '')
				return false;
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
				} else
					$current = $requestURI;
				
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
				$argNumber = intval($argNumber);
				$argData = null;

				// Optional
				$optional = false;
				if(substr($matches, 0, 1) === '?'){
					$matches = substr($matches, 1);
					$optional = true;
				}

				// Regex
				if(substr($matches, 0, 1) === ':'){
					$matches = substr($matches, 1);

					if(strpos($current, '/') !== false) return false; // Strict
					if(preg_match('/'.$matches.'/', $current, $match)){
						$argData = $match;
					} else return false;
				}

				// Match after
				elseif(substr($matches, 0, 1) === '*'){
					$argData = $current;
				}

				// No options
				elseif($matches === false || $matches === ''){
					if(strpos($current, '/') !== false) return false; // Strict
					$argData = $current;
				}

				// Check if the required param was not found
				if(!$optional && $argData === null)
					return false;

				// Prepare the argument data
				if($argNumber !== false)
					$args[$argNumber] = $argData;
				else
					$args[] = $argData;
			}

			$matched = true;
		}

		if(!$matched) return false;
		if($checkOnly) return $matched;

		// Handle controller
		if(!is_callable($func)){
			return;
		}

		// Handle middleware
		if($opts !== false){
			foreach($opts as $ware){
				# code...
			}
		}
		
		// Call callback function
		if(self::$instantOutput === false){
			ob_start();
			if(count($args) !== 0)
				print(call_user_func_array($func, $args));
			else print($func());

			if(Scarlets\Error::$hasError !== true)
				echo(ob_get_contents());
			else
				Scarlets\Error::$hasError = false;

			ob_end_clean();
		}
		else {
			if($args)
				print(call_user_func_array($func, $args));
			else print($func());
		}
		self::$statusCode = 200;

		return true;
	}
}

// Refer to 'Route' class itself
Route::$this = new Route;