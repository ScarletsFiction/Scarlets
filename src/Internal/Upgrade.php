<?php

$root = realpath(__DIR__."/../..");
$parent = realpath($root.'/..');

if($options === 'rollback'){
	if(!is_dir($parent.'/scarlets_backup')){
		echo("- Can't find old framework");
		return;
	}

	deleteContent($parent.'/scarlets', true);
	rename($parent.'/scarlets_backup', $parent.'/scarlets');
	echo(" - Rollback finished");
	return;
}

$opts = [
  'http'=>[
    'method'=>"GET",
    'header'=>"User-Agent: ScarletsFramework\n"
  ]
];

$context = stream_context_create($opts);

echo(" - Checking build status\n");
$status = file_get_contents('https://api.travis-ci.org/ScarletsFiction/Scarlets.svg?branch=master', 0, $context);
if(strpos($status, 'fail')!==false){
	echo("   Currently the framework is unstable");
	if($options !== 'force') return;
	echo("\n");
}

echo(" - Checking latest commit date\n");
$status = file_get_contents('https://api.github.com/repos/ScarletsFiction/Scarlets/branches/master', 0, $context);
$status = strtotime(json_decode($status, true)['commit']['commit']['committer']['date']);

$last = filemtime(__DIR__."/Console.php");

if($status <= $last){
	echo("   Looks like the framework already up to date");
	if($options !== 'force') return;
	echo("\n");
}

echo(" - Determining archive size\n");
$headers = get_headers('https://codeload.github.com/ScarletsFiction/Scarlets/zip/master', true);
if(isset($headers['Content-Length']))
	$filesize = round(intval($headers['Content-Length'])/1024);
else 
	$filesize = '?';

echo(" - Downloading repository ($filesize KB)\n");
file_put_contents('master.zip', file_get_contents('https://codeload.github.com/ScarletsFiction/Scarlets/zip/master'));

echo(" - Extracting files\n");
$zip = new ZipArchive;
$res = $zip->open('master.zip');

echo(" - Making backup\n");
try{
	deleteContent($parent.'/scarlets_backup', true);

	if(file_exists($parent.'/scarlets'))
		rename($parent.'/scarlets', $parent.'/scarlets_backup');
} catch(\Exception $e){
	echo("Access denied to the framework folder\n");
	echo("Make sure there are no application that using the folder\n");
	return;
}

$zip->extractTo($root.'/temp/');
$zip->close();

echo(" - Moving files\n");
rename($root.'/temp/Scarlets-master/composer.json', $root.'/composer.json');
rename($root.'/temp/Scarlets-master/LICENSE', $root.'/LICENSE');
rename($root.'/temp/Scarlets-master/README.md', $root.'/README.md');
rename($root.'/temp/Scarlets-master/require.php', $root.'/require.php');
rename($root.'/temp/Scarlets-master/src', $root.'/src');

echo(" - Delete temporary file\n");
try{
	deleteContent($root.'/temp', true);
	unlink('master.zip');
} catch(\Exception $e) {
	echo(" - Some temporary files couldn't be deleted\n ");
}

echo(" - Task finished\n");

function deleteContent($path, $pathAlso = true){
	if(!file_exists($path)) return;
	$iterator = new DirectoryIterator($path);
	foreach($iterator as $fileinfo){
		if($fileinfo->isDot()) continue;
		if($fileinfo->isDir() && deleteContent($fileinfo->getPathname(), true))
			@rmdir($fileinfo->getPathname());
		if($fileinfo->isFile())
			@unlink($fileinfo->getPathname());
	}
	if($pathAlso) rmdir($path);
	return true;
}