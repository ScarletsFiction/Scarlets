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
	public static $commandsDescription = [];
	public static $found = false;
	public static $args = false;

	public static function Initialization(){
		$argv = &$_SERVER['argv'];
		unset($argv[0]);

		include_once "Internal/Console.php";

		// Start the console
		if(count($argv) === 0)
			self::interactiveShell();

		// Start executing command
		else{
			if(in_array($argv[1], ['/h', '/?', '-h', '--help']))
				$argv = ['help'];
			self::interpreter(implode(' ', $argv));
		}
	}

	public static function interactiveShell(){
		// self::clear();
		echo("Welcome to ScarletsFramework!\n\n");
		$fp = fopen('php://stdin','r');
		$lastInput = microtime();

		$config = &\Scarlets\Config::$data;
		while(1){
			echo($config['app.console_user'].'> ');
			if(self::interpreter(rtrim(fgets($fp, 1024))))
		    	break;

 			// Too fast or caused by CTRL+Z then enter
 			$time = microtime();
		    if($lastInput === $time){
		    	echo('Shutting down Scarlets Console...');
		    	break;
		    }

		    $lastInput = $time;
		}

		fclose($fp);
		exit;
	}

	public static function &waitInput(){
		$fp = fopen('php://stdin','r');
		$temp = rtrim(fgets($fp, 1024));
		fclose($fp);
		return $temp;
	}

	public static function &hiddenInput(){
		$result = '';
		if(PHP_OS === 'WINNT' || PHP_OS === 'WIN32'){
			$result = exec(__DIR__ . '/Internal/Console_hiddenInput.bat');
			if(strtolower($result) === 'echo is off.')
				$result = '';
			echo("\n");
		}
		else
			$result = exec('read -s PW; echo $PW');
		return $result;
	}

	private static function interpreter($line){
		if($line === ''){
			echo("\r");
			return;
		}

		$pattern = explode(' ', $line);
		$firstword = $pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));
		$argsLen = count($pattern);

		if($argsLen === 1 && in_array($pattern[0], ['/h', '/?', '-h', '--help'])){
			if(isset(self::$commands["$firstword.h"])){
				echo("\n");
				$commands = &self::$commands["$firstword.h"];
				if(is_callable($commands)) $commands();
				else print_r($commands);
				echo("\n");
				return;
			}
		}

		$key = "$firstword.$argsLen";
		$commands = false;
		if(isset(self::$commands[$key])){
			$commands = &self::$commands[$key];

			// Check if zero argument
			if($argsLen === 0){
				$return = call_user_func($commands);
				if($return) echo("\n$return");
				echo("\n");
				return $return;
			}
		} else {
			// Check for registered special args
			$key = "$firstword.s";
			if(isset(self::$commands[$key])){
				$commands = &self::$commands[$key];
			}
		}

		if($commands){
			// $command = [[types], [args], callback];
			// if not have argument $command = callback;
			foreach($commands as &$command){
				$matched = false;
				$uniqueCheck = 0;

				// Check arguments
				$uniques = &$command[0];
				$args = &$command[1];
				for ($i=0; $i < count($args); $i++) {

					// It's unique
					if(isset($uniques[$uniqueCheck]) && $uniques[$uniqueCheck] === $i){
						$matched = true;
						$uniqueCheck++;
					} else {

						// Check for static argument patterns
						if(isset($args[$i]) && isset($pattern[$i]) && $args[$i] === $pattern[$i])
							$matched = true;

						else{
							$matched = false;
							continue 2;
						}
					}
				}

				if(count($args) > $argsLen) // Don't match empty space
					continue;

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
							self::$args = array_slice($pattern, $i);
							$arguments[$number] = implode(' ', self::$args);
							break;
						}
						$arguments[$number] = $pattern[$number];
					}
					$return = call_user_func_array($command[2], $arguments);
					if($return) echo("\n".$return);
					echo("\n");

					// Reset
					self::$args = false;
					self::$found = false;
					return $return;
				}
			}
		}

		if(isset(self::$commands["$firstword.h"])){
			$commands = &self::$commands["$firstword.h"];
			if(is_callable($commands)) $commands();
			else print_r($commands);
			echo("\n");
			return;
		}

		echo("$firstword command with $argsLen argument was not registered\n");
		return;
	}

	public static function args($pattern, $callback){
		if(self::$found) return; // Another callback already invoked
		if(is_array($pattern)){
			foreach ($pattern as &$value) {
				self::args($value, $callback);
			}
			return;
		}

		$pattern = explode(' ', $pattern);
		$patternLen = count($pattern);

		if(in_array('{*}', $pattern) === false && count(self::$args) !== $patternLen)
			return;

		$arguments = [];
		for ($i=0; $i < $patternLen; $i++) {
			if(strpos($pattern[$i], '{') === 0){
				$number = explode('{', $pattern[$i]);
				if($number[0] !== '') continue;

				$number = $number[1];
				$number = explode('}', $number);
				if($number[1] !== '') continue;

				if($number[0] === '*'){
					if(count(self::$args) === $i) // Don't match empty space
						return;

					self::$args = array_slice(self::$args, $i);
					$number = count($arguments);
					$arguments[$number] = implode(' ', self::$args);
					break;
				}
				elseif(!is_numeric($number[0]))
					continue;

				$arguments[$number[0]] = &self::$args[$i];
			}
			else {
				if($pattern[$i] !== self::$args[$i])
					return;
			}
		}

		$return = call_user_func_array($callback, $arguments);
		self::$found = true;
		if($return) echo("\n".$return);
	}

	/*
		> Command Register
		Command with no argument or help can only being registered once
	
		(pattern) ..
		(callback) ..
	*/
	public static function command($pattern, $callback, $description=''){
		if(is_array($pattern)){
			foreach ($pattern as &$value) {
				self::command($value, $callback);
			}

			if($description)
				self::$commandsDescription[explode(' ', $pattern[0])[0]] = $description;
			return;
		}

		$special = strpos($pattern, '{*}') !== false;
		$pattern = explode(' ', $pattern);
		$key = $pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));
		$patternLen = count($pattern);

		if($description)
			self::$commandsDescription[$key] = $description;

		if($special)
			$key = "$key.s";
		else
			$key = "$key.$patternLen";

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
		else self::$commands[$key] = &$callback;
	}

	public static function clear(){
		if(strncasecmp(PHP_OS, 'win', 3) === 0)
			popen('cls', 'w');
		else exec('clear');
	}

	public static function help($pattern, $callback){
		if(strpos($pattern, ' ') !== false)
			trigger_error("Console help's pattern can't have a space ($pattern)");

		self::$commands["$pattern.h"] = &$callback;
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

	public static function collection(){
		$commands = Console::$commands;
		$list = [];
		foreach ($commands as $key => $value) {
			$key = explode('.', $key);
			$type = array_pop($key);
			$key = implode('.', $key);

			if(isset(self::$commandsDescription[$key])){
				$list[$key] = self::$commandsDescription[$key] . ($type === 'h'?' {help}':'');
				continue;
			}
			
			if(isset($list[$key]))
				continue;

			if($type === 'h')
				$list[$key] = 'Have a help section';

			elseif($type === '0')
				$list[$key] = 'Function';

			else
				$list[$key] = 'No description';
		}
		return $list;
	}

	public static function chalk($text, $color = 'green'){
		$color_ = '37m';
		if($color === 'black')
			$color_ = '30m';
		else if($color === 'red')
			$color_ = '31m';
		else if($color === 'green')
			$color_ = '32m';
		else if($color === 'yellow')
			$color_ = '33m';
		else if($color === 'blue')
			$color_ = '34m';
		else if($color === 'magenta')
			$color_ = '35m';
		else if($color === 'cyan')
			$color_ = '36m';

		return sprintf("\x1b[%s%s\x1b[0m", $color_, $text);
	}

	public static function table($data){
		$spacing = [0,0,0,0,0,0];
		$len = count($data[0]) - 1; // We don't need to calculate the last column

		// Find longest text
		foreach($data as &$value){
			for($i=0; $i < $len; $i++){
				if($spacing[$i] < strlen($value[$i]))
					$spacing[$i] = strlen($value[$i]) + 2;
			}
		}

		$len++;
		// Give spacing and print
		foreach($data as &$value){
			for($i=0; $i < $len; $i++){
				print($value[$i]);
				if($i != $len-1)
					for ($a=0; $a < $spacing[$i] - strlen($value[$i]); $a++) { 
						print ' ';
					}
			}
			print("\n");
		}
	}
}