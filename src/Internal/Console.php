<?php
namespace Scarlets;
use \Scarlets;
use \Scarlets\Config;

/*
---------------------------------------------------------------------------
| Register internal function
---------------------------------------------------------------------------
|
| Description haven't added
|
*/

Console::command('exit', function(){
	return true;
}, 'Exit this console (CTRL+Z and enter)');

Console::command('cls', function(){
	Console::clear();
}, 'Clear console');

Console::command(['maintenance {0}', 'maintenance'], function($action = ''){
	$path = &\Scarlets::$registry['path.maintenance_file'];
	if($action === '')
		return file_exists($path) ? "Status: On" : "Status: Off";

	if(function_exists('opcache_reset')){
		opcache_reset();
		echo("Opcache flushed\n");
	}

	if($action === 'on'){
		if(file_exists($path) === false)
			file_put_contents($path, '');
		return "Maintenance: On";
	}
	elseif($action === 'off'){
		if(file_exists($path))
			unlink($path);
		return "Maintenance: Off";
	}
}, 'Switch maintenance mode');

Console::command(['upgrade {0}', 'upgrade'], function($options=0){
	include 'Upgrade.php';
}, 'ScarletsFramework upgrade');

Console::command('help', function(){
	echo("\nType [command /?] to see help section if provided\n\n");
	$list = Console::collection();
	$table = [['Command List', 'Description']];
	foreach ($list as $key => $value) {
		$table[] = [$key, $value];
	}
	Console::table($table);
}, 'Show registered command list');

Console::command(['schedule {0} {1}', 'schedule {0}', 'schedule'], function($action = '', $minute = 1){
	if($action === 'tick'){
		if(file_exists(Scarlets::$registry['path.maintenance_file']) === false)
			include Scarlets::$registry['path.app'].'/routes/schedule.php';
		return;
	}

	$appName = str_replace(['"', '\\', '#'], '_', Config::get('app', 'appname'));
	$scarletsCMD = Scarlets::$registry['path.app'];
	$cronFolder = '/etc/crond.d';
	$minute = intval($minute);

	if($action === 'install'){
		if(stripos(PHP_OS, 'win') !== false){
			return shell_exec("schtasks /create /sc minute /mo $minute /tn \"\\Scarlets\\PHP\\$appName\" /tr \"start /B \"$scarletsCMD/scarlets.cmd\" schedule tick\" 1>&2");
		}
		else{
			$whoami = str_replace(["\n", "\r", "\t"], '', shell_exec('whoami'));
			if(file_exists($cronFolder.'/scarlets-php.job')){
				$all = file_get_contents($cronFolder.'/scarlets-php.job');

				if(preg_match('/^.*?#'.preg_quote($appName).'#$/m', $all))
					return "CronJob already exist";
			}
			else $all = '';
			$all .=	"\n*/$minute * * * * $whoami \"$scarletsCMD/scarlets schedule tick\" &> /dev/null #$appName#";
			$all = str_replace(["\n\n", "\r\n\r\n"], "\n", $all);
			file_put_contents($cronFolder.'/scarlets-php.job', $all);
			return "CronJob installed";
		}
	}
	elseif($action === 'uninstall'){
		if(stripos(PHP_OS, 'win') !== false)
			echo shell_exec("schtasks /delete /tn \"\\Scarlets\\PHP\\$appName\" /f 1>&2");
		else{
			if(file_exists($cronFolder.'/scarlets-php.job')){
				$all = file_get_contents($cronFolder.'/scarlets-php.job');
				$all = preg_replace('/^.*?#'.preg_quote($appName).'#$/m', '', $all);
				file_put_contents($cronFolder.'/scarlets-php.job', $all);
			}
			return 'CronJob removed';
		}
	}
	else {
		if(stripos(PHP_OS, 'win') !== false)
			shell_exec("schtasks /query /tn \"\\Scarlets\\PHP\\$appName\" 1>&2");

		elseif(file_exists($cronFolder.'/scarlets-php.job'))
			return explode(' &>', str_replace(['*/', '* * * *'], '', file_get_contents($cronFolder.'/scarlets-php.job')))[0];
	}
}, 'Control task scheduler');

Console::help('schedule', 
	"schedule [action] [heartbeat]
 - action: tick, install, uninstall
 - heartbeat: system interval to check the schedule

It's recommended to use 5min heartbeat to avoid disk overheat"
);

Console::command(['serve {0} {1} {*}', 'serve {0} {1}', 'serve {0}', 'serve'], function($port=8000, $address='localhost', $options=0){
	if($options !== 0){
		$temp = explode(' ', $options);
		$options = 0;
		if(in_array('--verbose', $temp)) $options |= 1;
		if(in_array('--log', $temp)) $options |= 2;
	}

	// Swap if different
	if(!is_numeric($port) && is_numeric($address)){
		$addr = $port;
		$port = $address;
		$address = $addr;
	}

	if($port === '--verbose'){
		$options = 0;
		$options |= 1;
		$port = 8000;
	}

	Scarlets\Library\Server::start(is_numeric($port) ? $port : 8000, $address, $options);
}, 'Serve your app from your computer');

Console::help('serve', 
	"serve [port] [address] [options]
 - Address: localhost, network, IP Address
 - Available options: no-logs, silent"
);