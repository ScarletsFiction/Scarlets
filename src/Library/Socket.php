<?php
namespace Scarlets\Library;
use \Scarlets;

class Socket{
	/*
		> Create socket server

		(address) ..
		(port) ..
		(readCallback) ..
		(connectionCallback) ..
	*/
	public static function create($address, $port, $readCallback, $connectionCallback=0){
		set_time_limit(0);
		ob_implicit_flush();

		$address = $address ?: 'localhost';
		$sock = socket_create(AF_INET, SOCK_STREAM, 0);

		if($address === 'public')
			self::bindToPublic($sock, $port);
		else
			socket_bind($sock, $address, $port) or die('Could not bind to address');

		socket_listen($sock);
		$clients = [$sock];

		$write = $except = NULL; // We doesn't use this
		$garbageWaiting = 0; // Max to 100

		// Avoid too many function call in loop
		while(1){
			$read = $clients; // $read client that have data

			// Check if there are some data from client that can be read
			if(socket_select($read, $write, $except, 1) === 0)
				continue;

			// Check for new client connection
			if(in_array($sock, $read)){ // in_array is more faster (tested on apache benchmark)

				// Add the new client to the list
				$clients[] = $newsock = socket_accept($sock);
				unset($read[array_search($sock, $read)]);

				if($connectionCallback !== 0)
					$connectionCallback($newsock, 'connected');
			}

			foreach($read as &$read_sock){
				$data = @socket_read($read_sock, 2048);
				$keep = true;

				// when readCallback return true,  disconnect and remove the socket
				if(trim($data) !== '' && !$readCallback($read_sock, $data))
					continue;

				// Remove disconnected client from the list
				unset($clients[array_search($read_sock, $clients)]);
				$garbageWaiting++;

				if($garbageWaiting>100)
					$clients = array_values($clients);

				if($connectionCallback !== 0)
					$connectionCallback($read_sock, 'disconnected');

				socket_close($read_sock);
				unset($read_sock);
			}
		}
		socket_close($sock);
	}

	/*
		> Simple socket handler

		(readCallback) ..
		(address) ..
		(port) ..
	*/
	public static function simple($address, $port, $readCallback){
		set_time_limit(0);
		ob_implicit_flush();

		$address = $address ?: 'localhost';
		$sock = socket_create(AF_INET, SOCK_STREAM, 0);

		if($address === 'public')
			self::bindToPublic($sock, $port);
		else
			socket_bind($sock, $address, $port) or die('Could not bind to address');

		socket_listen($sock);

		// Avoid too many function call in loop
		while(1){
			$socket = socket_accept($sock);
			$data = @socket_read($socket, 2048);
			if($data === false){
				socket_close($socket);
				continue;
			}

			$readCallback($socket, $data);
			socket_close($socket);
		}
	}

	public static function ping($domain, $port=80)
	{
		$starttime = microtime(true);
		$file = @fsockopen($domain, $port, $errno, $errstr, 10);
		if(!is_resource($file))
			return 0;
		$stoptime = microtime(true);
		$status = 0;

		if(!$file) $status = -1;  // Site is down
		else{
			fclose($file);
			$status = ($stoptime - $starttime) * 1000;
			$status = floor($status);
		}
		return $status;
	}

	public static function connect($ip, $port, $protocol='tcp'){
		$socket = socket_create(AF_INET, SOCK_STREAM, $protocol === 'udp' ? SOL_UDP : SOL_TCP);

		if($socket === false)
			trigger_error(socket_strerror(socket_last_error()));

		$result = socket_connect($socket, $ip, $port);
		if ($result === false)
			trigger_error(socket_strerror(socket_last_error($socket)));

		return new SocketClient($socket);
	}

	private static function bindToPublic($sock, $port){
		$IPs = self::getIPAddress();
		foreach ($IPs as $value) {
			socket_bind($sock, $value, $port);
		}
	}

	public static function getIPAddress(){
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			$IPs = shell_exec('ipconfig | findstr IPv4');
		else
			$IPs = shell_exec("ifconfig | grep 'inet addr'");

		// Remove local address
		$IPs = preg_replace('/^.*127\..*/', '', $IPs);

		// Get the IP Address Array
		preg_match_all('/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/', $IPs, $matches);

		$IPs = [];
		foreach ($matches as $value) {
			if(!in_array($value[0], $IPs))
				$IPs[] = $value[0];
		}

		return $IPs;
	}
}

class SocketClient{
	private $socket;
	public function __construct(&$socket){
		$this->socket = $socket;
	}

	public function write($text){
		socket_write($this->socket, $text, strlen($text));
	}

	public function read($bytes=2048, $onChunk=null){
		$collect = '';

		while ($out = socket_read($this->socket, $bytes)) {
			if($onChunk !== null)
				$onChunk($out);
			else $collect .= $out;
		}

		return $collect;
	}

	public function disconnect(){
		socket_close($this->socket);
	}
}