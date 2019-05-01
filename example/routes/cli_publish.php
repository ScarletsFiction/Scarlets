<?php
/*
|--------------------------------------------------------------------------
| Secret Console
|--------------------------------------------------------------------------
|
| Warning! This file should not be uploaded to production server.
| This must only be available on local server, this was supposed for
| publishing any changes made on the local server and zip it.
| 
| You can freely handle the zip file like upload it to your server
| and unzip it through SSH as root user, or let another script
| handle it. Or upload via SFTP or something..
|
| Don't ever made your project folder writable instead by root user.
| Don't ever made your script handler readable instead by root user.
|
| This will scan your project's 'app, public, resources, routes'
| and generate zip in '/storage/framework/publish.zip'
| if changes was detected from last scan.
*/
use \Scarlets\Console;

$project = &\Scarlets::$registry['path.app'];

$time = $project.'/storage/framework/publish_time.json';
$oldList = $project.'/storage/framework/publish_last.json';
$scanFolder = ['/app', '/public', '/resources', '/routes'];
$ignoreFiles = ['cli_publish.php']; // Always ignore this file
$GMT = 7;

// Check last scan list
if(file_exists($oldList))
	$deleteList = json_decode(file_get_contents($oldList), true);
else $deleteList = [];

// ===== Collect Files =====
$lastTimestamp = 0;
if(file_exists($time)){
	$lastTimestamp = intval(file_get_contents($time));
	echo(Console::chalk("Collecting files after (".date('H:i:s - d M Y', $lastTimestamp + $GMT*3600).")\n", 'yellow'));
}
else echo(Console::chalk("Collecting all files\n", 'yellow'));

// Prepare the iterator
$iterator = new AppendIterator();
foreach ($scanFolder as &$value) {
	$dir = new RecursiveDirectoryIterator(realpath($project.$value));
	$iterator->append(new RecursiveIteratorIterator($dir));
}

$project .= DIRECTORY_SEPARATOR;

// Scan for new and deleted list
$newList = [];
$changedList = [];
foreach ($iterator as $filename => $cur) {
	if(!$cur->isDir()){
		$pathname = $cur->getPathname();
		if(in_array($cur->getFilename(), $ignoreFiles) === true)
			continue;

		$newList[] = $pathname;

		if($cur->getMTime() > $lastTimestamp)
    		$changedList[] = str_replace($project, '', $pathname);

    	// Remove from delete list
    	$i = array_search($pathname, $deleteList);
    	if($i !== false)
	    	array_splice($deleteList, $i, 1);
	}
}

// Save list for the next scan
file_put_contents($oldList, json_encode($newList));

$updatedCount = count($changedList);
$deleteCount = count($deleteList);
if($updatedCount !== 0)
	echo("There are ".Console::chalk($updatedCount, 'green')." files to be updated\n");
if($deleteCount !== 0)
	echo("There are ".Console::chalk($deleteCount, 'yellow')." files to be deleted\n");

if($updatedCount === 0 && $deleteCount === 0){
	echo("Nothing changed");
	return;
}

foreach ($deleteList as &$del) {
    $del = str_replace($project, '', $del);
}

// ===== Function: Zip files =====
$zipFiles = function() use(&$project, &$changedList, &$deleteList){
	$zipPath = $project.'/publish.zip';
	file_put_contents($project.'/publish_delete.json', $deleteList);

	echo("Zipping ".count($changedList)." files\n");

	$zip = new ZipArchive();
	$res = $zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if($res !== true){
		echo(Console::chalk("ZipArchive error code: ".$res, 'red'));
		return;
	}

	// Add file to zip
	foreach ($changedList as &$value) {
		$zip->addFile($value, str_replace($project, '', $value));
	}
	$zip->close();

	echo(Console::chalk("Zipping finished\n", 'green'));
};

// ===== Function: Sync files to the server via FTP Bridge =====
$FTPSync = function() use(&$project, &$changedList, &$deleteList){
	$remotePath = '/home/websites/my-project/';
	$localPath = $project;

	// Always use FTP to SFTP Bridge or another secure protocol
	echo("Connecting to FTP\n");
	$conn = ftp_connect('127.0.0.1', 2135);
	if(!ftp_login($conn, 'yourUser', 'yourPass')){
		echo(Console::chalk("Login was failed to the FTP", 'red'));
		return;
	}

	foreach ($changedList as &$value){
		if(ftp_put($conn, $remotePath.str_replace('\\', '/', $value), $localPath.$value, FTP_BINARY))
			echo Console::chalk("+ $value\n", 'green');
		else echo Console::chalk("+ $value\n", 'red');
	}

	foreach ($deleteList as &$value){
		if(ftp_delete($conn, $remotePath.str_replace('\\', '/', $value)))
			echo Console::chalk("- $value\n", 'green');
		else echo Console::chalk("- $value\n", 'red');
	}

	ftp_close($conn);

	// Flush opcache and check server
	$res = \Scarlets\Library\WebRequest::loadURL('https://example.com/api/opcache_reset');
	if($res === '[1]')
		echo("\n Server opcache was flushed!\n");

	$res = \Scarlets\Library\WebRequest::loadURL('https://example.com/api/ping');
	if($res !== '[1]')
		echo("\n Something was wrong with last update!\n");
};

// ===== There are many method that you can implement for publishing changes =====
$zipFiles(); // $FTPSync();
file_put_contents($time, time());
echo("Publish Finished!");