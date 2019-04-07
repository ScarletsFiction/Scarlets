<?php
namespace Scarlets\Extend;

class Arrays{
	public static function &sortByKey($array, $key, $ascending = true, $caseinsensitive = false){
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

	public static function &duplicates($array){
    	$duplicates = [];
    	$values = array_count_values($array);
    	foreach ($values as $value => $count) {
    		if($count > 1) $duplicates[] = $value;
    	}

    	return $duplicates;
	}

	public static function uniqueDifference($array1, $array2){
		$array1 = array_flip($array1);
		$array2 = array_flip($array2);

		$new = [];
		$unchanged = [];
		foreach ($array2 as $key => $value) {
			if(isset($array1[$key])){
				unset($array1[$key]);
				$unchanged[] = $key;
			}
			else $new[] = $key;
		}

		return [
			'new'=>$new,
			'unchanged'=>$unchanged,
			'deleted'=>array_flip($array1),
		];
	}

	public static function contains($str, $arr){
	    foreach($arr as &$a) {
	        if (stripos($str, $a) !== false)
	        	return true;
	    }
	    return false;
	}

	// Return ',' or ',1,'
	public static function encodeComma(&$array){
		if(count($array) === 0) return ',';

		return ','.implode(',', $array).',';
	}

	// Return [1]
	public static function decodeComma(&$text){
		if(strlen($text) <= 2)
			return [];

		return json_decode('['.substr($text, 1, -1).']', true);
	}

	public static function &scoreSimillar(&$cache, $value, $column = null, $id = null){
		$obtained = [];
		$lastLow = 1;
		$lastLowID = 0;
		$z = 0;
		$i = -1;
		foreach ($cache as &$ref) {
			if($id === null) $i++;

			if($column === null)
				$text = strtolower($ref);
			else $text = strtolower($ref[$column]);

			similar_text($text, $value, $score);
			$pendingScore = 0;

			// Improve accuracy
			if($score > 10 && $score < 80){
				$pos = strpos($text, $value);
				if($pos === false && $score < $lastLow)
					continue;

				if($pos === 0)
					$pendingScore = 101+($score/100);

				elseif(strpos($text, " $value ") !== false)
					$pendingScore = 103+($score/100);

				elseif(substr($text, $pos-1, 1) === ' ')
					$pendingScore = 102+($score/100);
			}
			elseif($score < $lastLow)
				continue;

			if($z >= 10){
				array_splice($obtained, $lastLowID, 1);

				$lastLow = $score;
				foreach ($obtained as $key => &$val) {
					if($val[1] < $lastLow && $pendingScore === 0){
						$lastLow = &$val[1];
						$lastLowID = $key;
					}
				}
			}
			else{
				if($score < $lastLow && $pendingScore === 0){
					$lastLow = $score;
					$lastLowID = $z;
				}
				$z++;
			}

			if($id !== null)
				$obtained[] = [$ref[$id], $pendingScore ?: $score];
			else $obtained[] = [$i, $pendingScore ?: $score];
		}

		$lastHigh = 0;
		$temp = [];
		foreach ($obtained as &$val) {
			$temp[$val[0]] = &$val[1];
			if($val[1] < 100 && $lastHigh < $val[1])
				$lastHigh = $val[1];
		}

		if($lastHigh === 100)
			$lastHigh = 80;

		// Normalize value
		$normalize = 100 - $lastHigh;
		foreach ($obtained as &$val) {
			if($val[1] > 100)
				$val[1] -= $normalize;
		}

		arsort($temp);
		return $temp;
	}
}