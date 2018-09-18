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
	public static $ended = false;
	private static $namespace = false;
	private static $prefix = false;
	private static $name = false;
	private static $middleware = false;
	private static $waitName = false;

	private static function implementCurrentScope(&$url, &$func, &$opts){
		if(self::$namespace && !is_callable($func))
			$func = implode('\\', self::$namespace).'\\'.$func;

		if(self::$prefix){
			if(substr($url, 0, 1) !== '/')
				$url = '/'.$url;

			$url = '/'.implode('/', self::$prefix).$url;
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

					$value = 'name:'.$name;
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
		private static function requestMethod($method, $url, $func, $opts){
			if(self::$namespace || self::$prefix || self::$name || self::$middleware)
				self::implementCurrentScope($url, $func, $opts);

			if(Scarlets::$isConsole)
				Route\Handler::register($method, $url, $func, $opts);

			elseif($_SERVER['REQUEST_METHOD'] === $method && self::handleURL($url, $func, $opts))
				return true;
		}

		public static function get($url, $func, $opts = false){
			return self::requestMethod('GET', $url, $func, $opts);
		}
		
		public static function post($url, $func, $opts = false){
			return self::requestMethod('POST', $url, $func, $opts);
		}
		
		public static function delete($url, $func, $opts = false){
			return self::requestMethod('DELETE', $url, $func, $opts);
		}
		
		public static function put($url, $func, $opts = false){
			return self::requestMethod('PUT', $url, $func, $opts);
		}
		
		public static function options($url, $func, $opts = false){
			return self::requestMethod('OPTIONS', $url, $func, $opts);
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
		private static $scopeConstrain = [];
		private static function scopeBased($part, $arg1, $func){
			$current = &self::${$part};
			if($current === false)
				$current = [];

			$current[] = $arg1;

			if(!$func){
				self::$scopeConstrain[] = $part;
				return new self;
			}
			
			$func();

			array_pop($current);
			if(count($current) === 0)
				$current = false;

			// Clear last constrain
			foreach (self::$scopeConstrain as &$var) {
				$ref = &self::${$var};
				if($ref !== false){
					array_pop($ref);
					if(count($ref) === 0)
						$ref = false;
				}
				unset($var);
			}
		}

		public static function namespaces($namespace, $func = false){
			return self::scopeBased('namespace', $namespace, $func);
		}
		
		public static function prefix($url, $func){
			return self::scopeBased('prefix', $url, $func);
		}
		
		public static function name($name, $func){
			return self::scopeBased('name', $name, $func);
		}
		
		public static function middleware($controller, $func){
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
		$matched = false;
		$args = [];

		if(substr($url, 0, 1) !== '/')
			$url = '/'.$url;

		$requestURI = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
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
		$middlewareCallback = false;
		if($opts !== false){
			foreach($opts as $ware){
				$middlewareCallback = Route\Middleware::callMiddleware($ware);
				if($middlewareCallback === false)
					return false;
			}
		}
		
		self::$statusCode = 200;
		
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
			else print(call_user_func($func));
		}

		if(is_callable($middlewareCallback))
			$middlewareCallback();

		return true;
	}
}

// Refer to 'Route' class itself
Route::$this = new Route;