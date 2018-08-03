<?php 

namespace Scarlets\Library;
const path = __DIR__."/library";

/*
---------------------------------------------------------------------------
| Scarlets Library
---------------------------------------------------------------------------
|
| This is where all of available library being listed. So it can
| be available on your auto-completion.
|
*/
use Scarlets\Library;

function Database($databaseID){
	include_once $library."Database.php";
	return Database\init($databaseID);
}

/*
---------------------------------------------------------------------------
| Notice for contributor
---------------------------------------------------------------------------
|
| Make sure you know how to do micro-optimization when developing
| PHP library. For example Regex is slower on PHP rather string
| manipulation, but faster on JavaScript.
| Because it's slower then you should implement the efficient method.
|
| Here you can compare each PHP function: http://phpbench.com/
|
*/