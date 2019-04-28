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
$zipPath = $project.'/storage/framework/publish.zip';
$oldList = $project.'/storage/framework/publish_last.json';
$deleteList = $project.'/storage/framework/publish_delete.json';
$scanFolder = ['/app', '/public', '/resources', '/routes'];
$ignoreFiles = ['cli_publish.php']; // Always ignore this file
$GMT = 7;

// Check last scan list
if(file_exists($oldList))
	$lastList = json_decode(file_get_contents($oldList), true);
else $lastList = [];

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

// Scan for new and deleted list
$newList = [];
$zipList = [];
foreach ($iterator as $filename => $cur) {
	if(!$cur->isDir()){
		$pathname = $cur->getPathname();
		if(in_array($cur->getFilename(), $ignoreFiles) === true)
			continue;

		$newList[] = $pathname;

		if($cur->getATime() > $lastTimestamp)
    		$zipList[] = $pathname;

    	// Remove from delete list
    	$i = array_search($pathname, $lastList);
    	if($i !== false)
	    	array_splice($lastList, $i, 1);
	}
}

// Save list for the next scan
file_put_contents($oldList, json_encode($newList));
file_put_contents($deleteList, json_encode($lastList));

$deleteCount = count($lastList);
if($deleteCount !== 0)
	echo("There are ".$deleteCount." files to be deleted\n");

if(count($zipList) === 0){
	echo("Nothing to publish");
	return;
}


// ===== Zipping files =====
use \Scarlets\Library\FileSystem\Localfile;
echo("Zipping ".count($zipList)." files\n");

if(file_exists($zipPath))
	unlink($zipPath);

$zip = new ZipArchive();
$res = $zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
if($res !== true){
	echo(Console::chalk("ZipArchive error code: ".$res, 'red'));
	return;
}

// Replace slashes
$winSlash = strpos($zipList[0], '\\') !== false;
if($winSlash === true)
	$project .= '\\';
else $project .= '/';

// Add file to zip
foreach ($zipList as &$value) {
	$zip->addFile($value, str_replace($project, '', $value));
}
$zip->close();

echo(Console::chalk("Zipping finished\n", 'green'));
file_put_contents($time, time());

// ===== Your next code here =====
echo("You need to upload and extract the zipped files to your server\n");