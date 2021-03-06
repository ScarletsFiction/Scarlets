<?php
namespace Scarlets;
use \Scarlets\Internal\Console\AutoShell;
use \Scarlets\Internal\Console\AutoComplete;

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
	public static $nested = []; // for autocompletion
	public static $commandsDesc = [];
	public static $found = false;
	public static $args = false;

	// $import = ['database'=>'scarletsfiction/scarlets'];
	public static $import = [];
	private static $importing = false; // string

	public static $registeredEnv = [];
	public static $currentEnv = [];

	public static function export($func){
		$func();
	}

	public static function environment($name, $func=false){
		if(self::$importing !== false)
			return;

		if($func !== false)
			return self::registerEnv($name, $func);

		self::$currentEnv[] = &$name;
	}

	public static function exitEnvironment(){
		array_pop(self::$currentEnv);
	}

	private static function registerEnv($name, $func=false){
		self::$registeredEnv[$name] = [];
		self::$commands = &self::$registeredEnv[$name];

		if($func !== false){
			$func();
		}
	}

	public static function Initialization(){
		self::$currentEnv['root'] = &self::$commands;

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

			self::interpreter(array_values($argv));
		}
	}

	public static function interactiveShell(){
		// self::clear();
		echo("Welcome to ScarletsFramework!\n\n");
		$lastInput = microtime();

		\Scarlets::$interactiveCLI = true;
		$config = &\Scarlets\Config::$data;

		if(PHP_OS === 'WINNT' || PHP_OS === 'WIN32'){
			if(false && self::compilePolyfill()){ // using console polyfill
				echo $config['app.console_user'].'> ';
				self::waitKey(0, function(&$char){
					$ord = ord($char);

					// exit on CTRL+C, CTRL+Z, or the listener was killed
					if($ord === 3 || $ord === 26 || $char === ''){
				    	echo('Shutting down Scarlets Console...');
				    	self::clearShadow();
						return true;
					}

					AutoShell::write($char);
				});
			}
			else{
				readline_completion_function(function($input, $index){
					return AutoComplete::readline($input, $index);
				});

				while(1){
					if(self::interpreter(readline($config['app.console_user'].'> ')))
				    	break;

					// Too fast or caused by CTRL+Z then enter
					$time = microtime();
				    if($lastInput === $time){
				    	echo('Shutting down Scarlets Console...');
				    	break;
				    }

				    $lastInput = $time;
				}
			}
		}
		else{
			// self::waitKey(0, function(&$char){
			// 	AutoShell::write($char);
			// });

			readline_completion_function(function($input, $index){
				return AutoComplete::readline($input, $index);
			});

			while(1){
				if(self::interpreter(readline($config['app.console_user'].'> ')))
					break;

				// Too fast or caused by CTRL+Z then enter
				$time = microtime();
			    if($lastInput === $time){
			    	echo('Shutting down Scarlets Console...');
			    	break;
			    }

			    $lastInput = $time;
				echo $config['app.console_user'].'> ';
			}
		}

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

	public static function interpreter($pattern){
		if(\Scarlets::$interactiveCLI === true){ // parse line
			if($pattern === ''){
				echo("\r");
				return;
			}

			preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $pattern, $pattern);
			$pattern = &$pattern[0];
		}

		$firstword = &$pattern[0];
		unset($pattern[0]);
		$pattern = array_values($pattern);
		$argsLen = count($pattern);

		try{
			if(in_array(end($pattern), ['/h', '/?', '-h', '--help'])){
				if($argsLen === 1 && isset(self::$commands["$firstword.h"])){ // display help if exist
					$commands = &self::$commands["$firstword.h"];
					echo("\n");
					if(is_callable($commands)) $commands();
					else print_r($commands);
					echo("\n");
					return;
				}
				else{ // display list of available command
					$available = [];
					foreach (self::$commands as $key => &$value) {
						$key = explode('.', $key)[0];

						if($key === $firstword){

						}
					}
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
					if($return){
						if(is_array($return) === true)
							echo json_encode($return, JSON_PRETTY_PRINT);
						else echo("\n$return");
					}
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
						}
						else {
							// true if all argument index exist
							if(isset($args[$i]) && isset($pattern[$i])){
								if($args[$i] === $pattern[$i]) // static args was equal
									$matched = true;

								else{ // dynamic args was equal
									$begin = explode('{', $args[$i])[0];
									if($begin !== '' && explode($begin, $pattern[$i])[0] === ''){
										$matched = false;
										continue 2;
									}

									// Check if match the end
									$end = explode('}', $args[$i]);
									if(count($end) === 1 || end($end) !== ''){
										$end = explode(end($end), $pattern[$i]);
										if(count($end) === 1 || end($end) !== ''){
											$matched = false;
											continue 2;
										}
									}

									$matched = true;
								}
							}

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
						$argumentsNamed = [];
						for ($i=0, $n=count($uniques); $i < $n; $i++) {
							$index = &$uniques[$i];

							$number = str_replace(['{', '}'], '', $args[$index]);
							if($number === '*'){
								self::$args = [];

								// Merge left arguments
								$number = count($arguments);
								$arguments[$number] = '';
								self::$args = array_slice($pattern, $index);
								$arguments[$number] = implode(' ', self::$args);
								break;
							}

							if(!is_numeric($number)){
								$argumentsNamed[$number] = $pattern[$index];
								continue;
							}

							$arguments[$number] = $pattern[$index];
						}

						ksort($arguments);

						if(count($argumentsNamed) !== 0){
							if(is_string($command[2]))
								$reflection = new \ReflectionMethod($command[2]);
							else
								$reflection = new \ReflectionFunction($command[2]);

							$params = $reflection->getParameters();

							for ($i=0, $n=count($params); $i < $n; $i++) {
								$name = $params[$i]->name;
							    if(isset($argumentsNamed[$name]))
							    	array_splice($arguments, $i, 0, $argumentsNamed[$name]);
							}
						}

						$return = call_user_func_array($command[2], $arguments);
						if($return){
							if(is_array($return) === true)
								echo json_encode($return, JSON_PRETTY_PRINT);
							else echo("\n".$return);
						}
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

			echo(self::chalk($firstword, 'yellow')." command with $argsLen argument was not registered\n");
		}catch(\Scarlets\SoftStop $e){}
	}

	public static function args($pattern, $callback){
		if(self::$found) return; // Another callback already invoked
		if(is_array($pattern)){
			foreach ($pattern as &$value) {
				self::args($value, $callback);
			}
			return;
		}

		preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $pattern, $pattern);
		$pattern = &$pattern[0];
		$patternLen = count($pattern);

		if(in_array('{*}', $pattern) === false && count(self::$args) !== $patternLen)
			return;

		$arguments = [];
		for ($i=0; $i < $patternLen; $i++) {
			if(strpos($pattern[$i], '{') === 0){
				$number = explode('{', $pattern[$i]);
				if($number[0] !== '') continue;

				$number = explode('}', $number[1]);
				if($number[1] !== '') continue;

				if($number[0] === '*'){
					if(count(self::$args) === $i) // Don't match empty space
						return;

					self::$args = array_slice(self::$args, $i);
					$number = count($arguments);
					$arguments[$number] = implode(' ', self::$args);
					break;
				}
				elseif(!is_numeric($number[0])){
					echo(self::chalk("Parameter index should be a numeric value but got `$number[0]`", 'red'));
					return;
				}

				$arguments[$number[0]] = &self::$args[$i];
			}
			else {
				if($pattern[$i] !== self::$args[$i])
					return;
			}
		}

		ksort($arguments);

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
				self::$commandsDesc[explode(' ', $pattern[0])[0]] = &$description;
			return;
		}

		$special = strpos($pattern, '{*}') !== false;
		$pattern = explode(' ', $pattern);
		$key = &$pattern[0];
		unset($pattern[0]);
		$pattern = array_values(array_filter($pattern));
		$patternLen = count($pattern);

		if($description)
			self::$commandsDesc[$key] = &$description;

		if(!isset(self::$nested[$key]))
			self::$nested[$key] = ['desc'=>&$description];
		elseif(self::$nested[$key]['desc'] === '')
			self::$nested[$key]['desc'] = &$description;

		if($special)
			$key = "$key.s";
		else
			$key = "$key.$patternLen";

		$uniqueIndex = [];
		for ($i=0; $i < $patternLen; $i++) {
			if(strpos($pattern[$i], '{') !== false){
				$number = explode('{', $pattern[$i]);
				if($number[0] !== '')
					$pattern[$i] = $number[1];

				$number = &$number[1];

				$number = explode('}', $number);
				if($number[1] !== '')
					$pattern[$i] = $number[0];

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

		if(!isset(self::$nested[$pattern]))
			self::$nested[$pattern] = ['desc'=>&$callback];
		elseif(self::$nested[$pattern]['desc'] === '')
			self::$nested[$pattern]['desc'] = &$callback;
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

			if(isset(self::$commandsDesc[$key])){
				$list[$key] = self::$commandsDesc[$key] . ($type === 'h'?' {help}':'');
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

	// https://misc.flogisoft.com/bash/tip_colors_and_formatting
	public static function chalk($text, $color = 'green', $lighter = false, $background = null){
		if(is_string($color) === false) // $lighter => isBackground
			return sprintf("\x1b[%s8;5;%sm%s\x1b[0m", $lighter ? 4 : 3, $color, $text);

		if($color === 'black')
			$color = '30';
		elseif($color === 'red')
			$color = '31';
		elseif($color === 'green')
			$color = '32';
		elseif($color === 'yellow')
			$color = '33';
		elseif($color === 'blue')
			$color = '34';
		elseif($color === 'magenta')
			$color = '35';
		elseif($color === 'cyan')
			$color = '36';
		else $color = '37';

		if($background !== null){
			if($background === true)
				$color .= ";4$color[1]";
			elseif($background === 'black')
				$color .= ';40';
			elseif($background === 'red')
				$color .= ';41';
			elseif($background === 'green')
				$color .= ';42';
			elseif($background === 'yellow')
				$color .= ';43';
			elseif($background === 'blue')
				$color .= ';44';
			elseif($background === 'magenta')
				$color .= ';45';
			elseif($background === 'cyan')
				$color .= ';46';
			else $color .= ';47';
		}

		return sprintf("\x1b[%s;%sm%s\x1b[0m", $lighter ? 1 : 0, $color, $text);
	}

	private static function rebuildCLIStyle(){
		$config = [
			' bg-black>'=>">\x1b[40m",
			' bg-red>'=>">\x1b[41m",
			' bg-green>'=>">\x1b[42m",
			' bg-yellow>'=>">\x1b[43m",
			' bg-blue>'=>">\x1b[44m",
			' bg-magenta>'=>">\x1b[43m",
			' bg-cyan>'=>">\x1b[46m",
			' bg-gray>'=>">\x1b[47m",

			'<black>'=>"\x1b[30m",		'</black>'=>"\x1b[0m",
			'<red>'=>"\x1b[31m",		'</red>'=>"\x1b[0m",
			'<green>'=>"\x1b[32m",		'</green>'=>"\x1b[0m",
			'<yellow>'=>"\x1b[33m",		'</yellow>'=>"\x1b[0m",
			'<blue>'=>"\x1b[34m",		'</blue>'=>"\x1b[0m",
			'<magenta>'=>"\x1b[33m",	'</magenta>'=>"\x1b[0m",
			'<cyan>'=>"\x1b[36m",		'</cyan>'=>"\x1b[0m",
			'<gray>'=>"\x1b[37m",		'</gray>'=>"\x1b[0m",

			'<black lighter>'=>"\x1b[1;30m",
			'<red lighter>'=>"\x1b[1;31m",
			'<green lighter>'=>"\x1b[1;32m",
			'<yellow lighter>'=>"\x1b[1;33m",
			'<blue lighter>'=>"\x1b[1;34m",
			'<magenta lighter>'=>"\x1b[1;33m",
			'<cyan lighter>'=>"\x1b[1;36m",
			'<gray lighter>'=>"\x1b[1;37m",

			'<b>'=>"\x1b[1m",			'</b>'=>"\x1b[22m",
			'<d>'=>"\x1b[2m",			'</d>'=>"\x1b[22m",
			'<i>'=>"\x1b[3m",			'</i>'=>"\x1b[23m",
			'<u>'=>"\x1b[4m",			'</u>'=>"\x1b[24m",
			'<uu>'=>"\x1b[21m",			'</uu>'=>"\x1b[24m",
			'<cu>'=>"\x1b[4:3m",		'</cu>'=>"\x1b[4:0m",
			'<blink>'=>"\x1b[5m",		'</blink>'=>"\x1b[25m",
			'<r>'=>"\x1b[7m",			'</r>'=>"\x1b[27m",
			'<invisible>'=>"\x1b[8m",	'</invisible>'=>"\x1b[28m",
			'<st>'=>"\x1b[9m",			'</st>'=>"\x1b[29m",
			'<ol>'=>"\x1b[53m",			'</ol>'=>"\x1b[55m",
			'<link '=>"\x1b]8;;",		' src="'=>"%%z$", '%%z$">'=>"\x1b[m",	'</link>'=>"\x1b]8;;\x1b\\",
		];

		self::$style = [array_keys($config), array_values($config)];
	}

	private static $style = null;
	public static $customStyle = null;
	public static function &style($text){
		if(self::$style === null)
			self::rebuildCLIStyle();

		$text = str_replace(self::$style[0], self::$style[1], $text);

		// Check for user defined styles
		if(self::$customStyle !== null && strlen(str_replace(self::$customStyle, '', $text)) !== strlen($text))
			self::implementCustomStyle($text);

		return $text;
	}

	private static $customStyles = null;
	public static function implementCustomStyle(&$text){
		foreach (self::$customStyles as $search => &$rep) {
			if(is_array($rep)){
				$text = preg_replace_callback('/'.preg_quote($search, '/').'/', function()use(&$rep){
					return $rep[0][mt_rand(0, $rep[1])];
				}, $text);
			}
			else $text = str_replace($search, $rep, $text);
		}
	}

	public static function customStyle($data){
		self::$customStyle = array_keys($data);

		foreach (self::$customStyle as &$tag) {
			if(isset($data[$tag]['replacement'])){
				$rep = &$data[$tag]['replacement'];
				$tag = "<$tag/>";
				self::$customStyles[$tag] = [&$rep, count($rep) - 1];
			}
		}
	}

	public static function table($data){
		$spacing = [0,0,0,0,0,0,0,0,0,0,0];
		$len = count(reset($data)) - 1; // We don't need to calculate the last column

		// Find longest text
		foreach($data as &$value){
			$i = 0;
			foreach($value as &$space){
				if($spacing[$i] < strlen($space))
					$spacing[$i] = strlen($space) + 2;
				$i++;
			}
		}

		$len++;
		// Give spacing and print
		foreach($data as &$value){
			$i = 0;
			foreach($value as &$space){
				print($space);
				if($i != $len-1)
					for ($a=0; $a < $spacing[$i] - strlen($space); $a++) {
						print ' ';
					}
				$i++;
			}
			print("\n");
		}
	}

	# ref: http://tldp.org/HOWTO/Bash-Prompt-HOWTO/x361.html
	public static function resetLine($text){
		echo "\r\033[K$text";
	}

	public static function saveCursor(){
		echo "\033[s";
	}

	public static function loadCursor(){
		echo "\033[u";
	}

	public static function hideCursor(){
		echo "\e[?25l";
	}

	public static function showCursor(){
		echo "\e[?25h";
	}

	private static $shadowLine = 0;
	public static function writeShadow($text){
		echo "\e[?25l\033[s"; //save
		echo str_repeat("\n\r\033[K", self::$shadowLine); // clear last line
		echo "\033[u\e[?25h"; //load
		echo "\e[90m$text\x1b[0m"; //write on gray color
		echo "\033[u\e[?25h"; //load

		self::$shadowLine = substr_count($text, "\n");
	}

	public static function clearShadow(){
		echo "\e[?25l\033[s"; //save
		echo str_repeat("\n\r\033[K", self::$shadowLine); // clear last line
		echo "\033[u\e[?25h"; //load

		self::$shadowLine = 0;
	}

	public static function size(){
		$line = explode("\n    ", `mode`);
		$found = ['lines'=>0, 'columns'=>0];
		foreach ($line as &$value) {
			if(strpos($value, 'Lines:') !== false)
				$found['lines'] = trim(explode(':', $value)[1]);
			elseif(strpos($value, 'Columns:') !== false)
				$found['columns'] = trim(explode(':', $value)[1]);
		}
		return $found;
	}

	private static $oldConsoleContent = '';
	public static function redraw($content){
		// ToDo: write array to console window
	}

	private static $shutdownWaitKey = false;
	// backspace-> "\b" ; tab-> "\t" ; enter-> "\n" ; space-> " "
	public static function &waitKey($timeout = false, $callbackLoop = false){
	    // If you find this polyfill and want to copy it, please credit to StefansArya
		$polyfill = false;

	    // Add polyfill (ToDo: non blocking)
	    if(is_callable('readline_callback_handler_install') === false){
	    	if(self::compilePolyfill() === false)
	    		return $polyfill;

	    	$polyfill = proc_open(__DIR__.'/Internal/Console_getch.exe', [
	    	    STDIN,
	    	    ['pipe', 'w']
	    	], $pipes);

	   		$stdin = $pipes[1];

	   		if(self::$shutdownWaitKey === false)
		   		\Scarlets::onShutdown(function(){
		   			exec('taskkill /f /im Console_getch.exe 2> NULL');
		   		}, true);
	    }
	    else{
	    	readline_callback_handler_install('', function(){});
	   		$stdin = STDIN;
	    }

	   	stream_set_blocking($stdin, 0);

	   	if($timeout === 0 || $timeout === null)
	   		$timeout = false;

	    if($timeout !== false){
	        $timeout *= 1000000;

	        if(stream_get_meta_data($stdin)['blocked'] === true)
	        	echo self::chalk("[Win32 bug] stream timeout can't be triggered\n", 'red');
	    }

	    $read = [$stdin];
	    $write = $except = NULL; // We doesn't use this

	    if($callbackLoop){
	        while(true){
	            $read_ = $read; // Make a copy

	            if($timeout !== false)
	                stream_select($read_, $write, $except, 0, $timeout);

	            if(count($read_) === 0){
	                if($callbackLoop(false) === true)
	                    break;

	                continue;
	            }

	        	if($polyfill !== false)
		        	$char = str_replace("\r\n", '', fread($stdin, 64));
		        else $char = fread($stdin, 64);

	            if($callbackLoop($char) === true)
	                break;
	        }

	        if($polyfill === false)
	        	readline_callback_handler_remove();
	        else exec('taskkill /f /im Console_getch.exe 2> NULL');

	        return $write;
	    }
	    else{
	        if($timeout !== false)
	            if(false === stream_select($read, $write, $except, 0, $timeout))
	            	die("stream_select failed");

	        if(count($read) === 0)
	            return $write;

	        if($polyfill !== false){
		        $char = str_replace("\r\n", '', fread($stdin, 64));

		       	exec('taskkill /f /im Console_getch.exe 2> NULL');
		    }
		    else{
		    	$char = fread($stdin, 64);
		    	readline_callback_handler_remove();
		    }

	        return $char;
	    }
	}

	private static function compilePolyfill(){
		if(file_exists(__DIR__.'/Internal/Console_getch.exe'))
			return true;

		echo "\nCompiling console polyfill for Win32...";

		$use = 'gcc';
		if(exec("which $use 2> NULL") === ''){
			$use = 'clang';

			if(exec("which $use 2> NULL") === ''){
				$use = 'cl';

				if(exec("which $use 2> NULL") === ''){
					echo "\nCompile failed: gcc, clang, or cl was not found";
					return false;
				}
			}
		}

		exec("$use ".__DIR__."/Internal/Console_getch.cpp -O3 -o ".__DIR__."/Internal/Console_getch.exe");
		return true;
	}

	// $nested = [$name=>$nest]
	public static function nested($name, $nest){
		$nested[$name] = &$nest;
	}
}