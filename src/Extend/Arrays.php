<?php
namespace Scarlets\Extend;

class Arrays{
	public static function sortByKey($array, $key, $ascending=true, $caseinsensitive=false)
	{
		$sort = $ascending ? SORT_ASC : SORT_DESC;
		$temp = []; $temp_ = $array;
		foreach ($temp_ as $key => $row) {
   			if($caseinsensitive) $temp[$key] = strtolower($row[$part]);
   			else $temp[$key] = $row[$part];
		}
		array_multisort($temp, $sort, $temp_);
		return $temp_;
	}
}