<?php
namespace Scarlets\Internal;

// For throwing an finish event on the middle of execution
class ExecutionFinish extends \Exception{
	public $data;
	public function __construct($data = false){
		$this->data = $data;
	}
}

class Pattern{
	public static function &parse($pattern, $text, $splitter){
		$pattern = explode('{', $pattern);
		$false = false;

		// Match beginning
		$temp = explode($pattern[0], $text, 2);
		if($temp[0] !== '')
			return $false;

		// Remove first match
		$text = &$temp[1];
		unset($pattern[0]);

		// Check all {param}
		foreach ($pattern as &$matches) {
			$matches = explode('}', $matches);
			$param_obtained = false;

			// Replace temporary URL
			if($matches[1] !== ''){
				$current = explode($matches[1], $text, 2);

				if(count($current) === 1)
					return $false;

				$param_obtained = true;

				$text = &$current[1];
				$current = &$current[0]; // Extracted from text
			}
			else $current = &$text;

			$matches = &$matches[0];

			// Find param number
			$argNumber = '';
			for ($i=0, $n=strlen($matches); $i < $n; $i++) {
				$temp = $matches[$i];
				if(!is_numeric($temp)){
					if($i === 0)
						$argNumber = false;

					break;
				}
				$argNumber .= $temp;
			}

			if($argNumber !== false)
				$argNumber = intval($argNumber);

			// Get the characters
			elseif($i === 0){
				for ($i=0; $i < $n; $i++) {
					$temp = $matches[$i];
					if($temp === '.' || $temp === '?' || $temp === '*' || $temp === ':')
						break;

					$argNumber .= $temp;
				}
			}

			// Get string after param number
			$matches = substr($matches, $i);
			$argData = null;
			$options = 0;

			if($matches !== ''){
				// Get symbol before regex
				$test = explode(':', $matches, 2);

				// Find out if it's as parameter index
				if(strpos($test[0], '.') !== false){
					$options |= 1;
				}

				// Optional Pattern
				if(strpos($test[0], '?') !== false){
					$matches = substr($matches, 1);
					$options |= 2;
				}

				// Match after
				if(strpos($test[0], '*') !== false){
					$options |= 4;
					$argData = &$current;
				}

				// Regex Pattern
				elseif(count($test) === 2){
					$matches = &$test[1];

					if(strpos($current, $splitter) !== false)
						return $false; // Strict

					if(preg_match("/$matches/", $current, $match)){
						$argData = &$match;
						unset($match);
					}
					else return $false;
				}

				// No options
				else{
					if(strpos($current, $splitter) !== false)
						return $false; // Strict

					$argData = &$current;
				}
			}
			else{
				if(strpos($current, $splitter) !== false)
					return $false; // Strict

				$argData = &$current;
			}

			// Prepare the argument data
			if($argNumber !== false)
				$args[$argNumber] = $argData;
			else
				$args[] = $argData;

			// Check if the required param was not found
			if(!($options & 2) && $argData === null)
				return $false;

			elseif($param_obtained === false) {
				if(is_array($argData))
					$argData = $argData[0];

				$split = explode($argData, $text, 2);
				$text = isset($split[1]) ? $split[1] : $split[0];
			}
		}

		if(!($options & 4) && strlen($text) !== 0)
			return $false;

		$args['>_option'] = $options;
		return $args;
	}
}