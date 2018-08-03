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