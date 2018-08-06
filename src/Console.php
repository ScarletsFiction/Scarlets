<?php 

namespace Scarlets;

/*
---------------------------------------------------------------------------
| Scarlets Console
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

class Console{

	/* 
		> Registered Commands

		$commands[firstword.argCount] = [
			callback1, // only if 'argCount' is '0' firstword -> (func)
			[[uniqueIndexes], [arg0], callback1], // firstword {0} -> ([0], ['{0}'], func)
			[[uniqueIndexes], [arg0, arg1], callback2] // firstword {0} after {1} -> ([0, 2], ['{0}', 'after', '{1}'], func)
		];
	*/
	public static $commands = [];
	public static $success = false;
	public static $args = false;

	public static function Initialization(){
		$argv = $_SERVER['argv'];
		unset($argv[0]);

		if(count($argv) === 0)
			self::interactiveShell();
		else
			self::interpreter(implode(' ', $argv));
	}

	public static function interactiveShell(){
		// self::clear();
		echo("Welcome to ScarletsFramework!\n\n");
		$fp = fopen("php://stdin","r");
		$lastInput = microtime();

		$config = &\Scarlets::$registry['config'];
		while(1){
			echo($config['app.console_user']."> ");
			if(self::interpreter(rtrim(fgets($fp, 1024))))
		    	break;

 			// Too fast or caused by CTRL+Z then enter
 			$time = microtime();
		    if($lastInput === $time)
		    	break;

		    $lastInput = $time;
		}

		fclose($fp);
		exit;
	}

	public static function interpreter($line){
		if($line === ''){
			echo("\r");
			return;
		}

		$pattern = explode(' ', $line);
		$firstword = $pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));
		$argsLen = count($pattern);

		$key = $firstword.'.'.$argsLen;
		$commands = false;
		if(isset(self::$commands[$key])){
			$commands = &self::$commands[$key];
			// Check if zero argument
			if($argsLen === 0){
				$return = false;
				// Call each registered function
				for ($i=0; $i < count($commands); $i++) {
					$return = call_user_func($commands[$i]) || $return;
				}
				echo("\n");
				return $return;
			}
		} else {
			// Check for registered special args
			$key = $firstword.'.s';
			if(isset(self::$commands[$key])){
				$commands = &self::$commands[$key];
			}
		}

		if(!$commands){
			if(isset(self::$commands[$firstword.'.h'])){
				$return = call_user_func(self::$commands[$firstword.'.h']);
				echo("\n");
				return $return;
			}

			echo("$firstword command with ".$argsLen." argument was not registered\n");
			return;
		}

		// $command = [[types], [args], callback];
		// if not have argument $command = callback;
		foreach($commands as &$command){
			$matched = false;
			$uniqueCheck = 0;

			// Find best match

			//var_dump($command);exit;

			// Check arguments
			$uniques = &$command[0];
			$args = &$command[1];
			for ($i=0; $i < $argsLen; $i++) {

				// It's unique
				if($uniques[$uniqueCheck] === $i){
					$matched = true;
					$uniqueCheck++;
					continue;
				}

				// Check for static argument patterns
				if($args[$i] === $pattern[$i])
					$matched = true;
				else{
					$matched = false;
					break;
				}
			}

			if($matched){
				// Process the unique arguments
				$arguments = [];
				for ($i=0; $i < count($uniques); $i++) {
					$number = str_replace(['{', '}'], '', $args[$uniques[$i]]);
					if($number === '*'){
						self::$args = [];

						// Merge left arguments
						$number = count($arguments);
						$arguments[$number] = '';
						self::$args = array_slice($pattern, $i + 1);
						$arguments[$number] = implode(' ', self::$args);
						break;
					}
					$arguments[$number] = $pattern[$number];
				}
				$return = call_user_func_array($command[2], $arguments);
				echo("\n");

				// Reset
				self::$args = false;
				self::$success = false;
				return $return;
			}
		}
	}

	public static function args($pattern, $callback){
		if(self::$success) return; // Another args already finished
		$pattern = explode(' ', $pattern);
		// self::$args

		$uniqueIndex = [];
		for ($i=0; $i < $patternLen; $i++) {
			if(strpos($pattern[$i], '{') === 0){
				$number = explode('{', $pattern[$i]);
				if($number[0] !== '') continue;

				$number = $number[1];
				$number = explode('}', $number);
				if($number[1] !== '') continue;

				if(!is_numeric($number[0]) && !$number[0] === '*') continue;
				$uniqueIndex[] = intval($number[0]);
			}
		}

		if(true){
			$return = call_user_func_array($command[2], $arguments);
			self::$success = true;
		}
	}

	/*
		> Command Register
		Description here
	
		(pattern) ..
		(callback) ..
	*/
	public static function command($pattern, $callback){
		$special = strpos($pattern, '{*}') !== false;
		$pattern = explode(' ', $pattern);
		$key = $pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));
		$patternLen = count($pattern);

		if($special)
			$key = $key.'.s';
		else
			$key = $key.'.'.$patternLen;

		$uniqueIndex = [];
		for ($i=0; $i < $patternLen; $i++) {
			if(strpos($pattern[$i], '{') === 0){
				$number = explode('{', $pattern[$i]);
				if($number[0] !== '') continue;

				$number = $number[1];
				$number = explode('}', $number);
				if($number[1] !== '') continue;

				if(!is_numeric($number[0]) && !$number[0] === '*') continue;
				$uniqueIndex[] = $i;
			}
		}

		if($patternLen)
			self::$commands[$key][] = [&$uniqueIndex, &$pattern, &$callback];
		else self::$commands[$key][] = &$callback;
	}

	public static function clear(){
		if(strncasecmp(PHP_OS, 'win', 3) === 0)
			popen('cls', 'w');
		else exec('clear');
	}

	public static function help($pattern, $callback){
		if(strpos($pattern, ' ') !== false)
			throw new \Exception("You can't use space on console help's pattern ($pattern)");

		self::$commands[$pattern.'.h'] = &$callback;
	}

	public static function isConsole(){
	    if(defined('STDIN'))
	        return true;

	    if(php_sapi_name() === 'cli')
	        return true;

	    if(array_key_exists('SHELL', $_ENV))
	        return true;

	    if(empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) 
	        return true;

	    if(!array_key_exists('REQUEST_METHOD', $_SERVER))
	        return true;

	    return false;
	}
}