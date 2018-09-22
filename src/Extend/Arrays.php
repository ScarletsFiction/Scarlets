<?php
namespace Scarlets\Extend;

class Arrays{
	public static function &sortByKey($array, $key, $ascending=true, $caseinsensitive=false)
	{
		$sort = $ascending ? SORT_ASC : SORT_DESC;
		$temp = [];
		$temp_ = &$array;
		foreach ($temp_ as $part => $row) {
   			if($caseinsensitive) $temp[$part] = strtolower($row[$key]);
   			else $temp[$part] = &$row[$key];
		}
		array_multisort($temp, $sort, $temp_);
		return $temp_;
	}
	
	public static function &duplicates($array)
	{
    	$duplicates = [];
    	$values = array_count_values($array);
    	foreach ($values as $value => $count) {
    		if($count > 1) $duplicates[] = $value;
    	}

    	return $duplicates;
	}
}