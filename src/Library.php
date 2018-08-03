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
//use Scarlets\Library;

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
| manipulation, but faster on Javascript.
|
| Here you can compare each PHP function: http://phpbench.com/
|
| Having too many function call or too many classes could be slow.
| Array type on PHP is more faster rather than Object
| You can evaluate it from 'Test_PHPInterpreter.php'
| But on Javascript, Object is the clear winner.
|
| Iterating with long variable reference also slower
| for(loop)
| 	$data->key->array[$i];
|
| The efficient way is saving the reference first
| $ref = $data->key->array;
| for(loop)
| 	$ref[$i];
|
| Because it's slower then you should implement the efficient method.
|
*/