<?php

/*
---------------------------------------------------------------------------
| SFDatabase Redis
---------------------------------------------------------------------------
|
| This library can help you connect to Redis as structured database.
| Redis is fast for key-value, but you can't filter/match the result
| from redis. It would be a good choice for caching realtime data
| instead be the main database.
|
*/
namespace Scarlets\Library\Database;

class Redis{
	public $connection;
	public $type = 'Redis';
	public $debug = false;
	private $structure = null;
	private $conn;

	public function __construct($options){
		// Default options
		if(!isset($options['host'])) $options['host'] = '127.0.0.1';
		if(!isset($options['username'])) $options['username'] = 'root';
		if(!isset($options['password'])) $options['password'] = '';
		if(!isset($options['port'])) $options['port'] = 6379;
		if(!isset($options['database'])) trigger_error('Redis database index was not specified');
		if(!isset($options['structure'])) trigger_error('Redis table structure was not defined');
		$this->structure = &$options['structure'];

		$this->connection = new \Redis;
		$this->conn = &$this->connection;
		$this->conn->connect($options['host'], $options['port']);
		$this->conn->select($options['database']);
		$this->conn->auth($options['password']);
		$this->conn->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

		if(isset($options['prefix']) && $options['prefix'] != false)
			$this->conn->setOption(\Redis::OPT_PREFIX, $options['prefix']);
	}

	// Return true if all condition meets, return false if condition was not meet
	private function operation(&$data, &$rules, $ORCondition, &$key){
		$currentCondition = !$ORCondition;

		foreach($rules as $ruleKey => &$rule){
			$prop = explode('#', $ruleKey, 2)[0]; // Clean first
			$prop = explode('[', $prop, 2);
			$oper = count($prop) !== 1 ? explode(']', $prop[1], 2)[0] : false; // Operator
			$prop = &$prop[0]; // Property key

			$operationCondition = true;

			// Recurse again
			if($prop === 'AND')
				$operationCondition = $this->operation($data, $rule, false, $key);

			// Recurse again
			else if($prop === 'OR')
				$operationCondition = $this->operation($data, $rule, true, $key);

			else if($oper === false){ // Equal to
				if(is_array($rule)){
					if(strpos($rule, $data[$prop]) === false)
						$operationCondition = false; // When nothing match
				}
				else if($data[$prop] != $rule)
					$operationCondition = false; // When "not equal to"
			}

			else if($oper === '!'){ // Not equal to
				if(is_array($rule)){
					if(strpos($rule, $data[$prop]) !== false)
						$operationCondition = false; // When something match
				}
				else if($data[$prop] == $rule)
					$operationCondition = false; // When "equal to"
			}

			else if($oper === '>'){ // Greater than
				if($data[$prop] <= $rule)
					$operationCondition = false; // When "lower or equal to"
			}

			else if($oper === '>='){ // Greater or equal
				if($data[$prop] < $rule)
					$operationCondition = false; // When "lower than"
			}

			else if($oper === '<'){ // Lower than
				if($data[$prop] >= $rule)
					$operationCondition = false; // When "more than or equal"
			}

			else if($oper === '<='){ // Lower or equal
				if($data[$prop] > $rule)
					$operationCondition = false; // When "more than"
			}

			else if($oper === '><'){ // Between 2 value
				if($data[$prop] <= $rule[0] || $data[$prop] >= $rule[1])
					$operationCondition = false; // When "not between 2 value or equal"
			}

			else if($oper === '=><='){ // Between 2 value
				if($data[$prop] < $rule[0] || $data[$prop] > $rule[1])
					$operationCondition = false; // When "not between 2 value"
			}

			else if($oper === '<>'){ // Not between than 2  value
				if($data[$prop] >= $rule[0] || $data[$prop] <= $rule[1])
					$operationCondition = false; // When "between 2 value or equal"
			}

			else if($oper === '<=>'){ // Not between than 2  value
				if($data[$prop] > $rule[0] || $data[$prop] < $rule[1])
					$operationCondition = false; // When "between 2 value"
			}

			else if(strpos($oper, '~') !== false){ // Data likes
				$likeCode = 1; // 1 = %value%, 2 = %value, 3 = value%
				$regexed = [];

				if(is_array($rule) === false)
					$rule = [$rule];

				foreach($rule as &$temp){
					if($temp[0] === '%' && substr($temp, -1) === '%'){
						$likeCode = 1;
						$temp = substr($temp, 1, -1);
					}

					else if($temp[0] === '%'){
						$likeCode = 2;
						$temp = substr($temp, 1);
					}

					else if(substr($temp, -1) === '%'){
						$likeCode = 3;
						$temp = substr($temp, 0, -1);
					}

					$temp = $temp.replace(regexEscape, '\\$&');

					if($likeCode === 2)
						$temp = "$temp$";

					else if($likeCode === 3)
						$temp = "^$temp";

					$regexed[] = $temp;
				}

				$exist = preg_match('/'.implode('|', $regexed).'/i', $data[$prop]) !== false;

				if(strpos($oper, '!') !== false) // Data not like
					if($exist) $operationCondition = false; // When "have match"

				else if(!$exist) // Data like
					$operationCondition = false; // When "not match"
			}

			if($ORCondition)// OR
				$currentCondition = $currentCondition || $operationCondition;

			else if($operationCondition === false){ // AND
				$currentCondition = false;
				break;
			}
		}

		return $currentCondition;
	}

	private $noCache = false;
	private function &doSearch(&$pattern, &$where, $withFields = false){
		$conn = &$this->conn;
		$structure = &$this->structure[$pattern];

		// Reduce some pattern by OR operation
		if(isset($where['OR'])){
			$struct = $structure;
			$OR = json_encode($where['OR']);

			for($i = count($struct)-1; $i >= 0; $i--){
				// false === false
				if(strpos($OR, '"'.$struct[$i]."[") === strpos($OR, '"'.$struct[$i].'"'))
					continue;

				// Remove from pattern if found
				unset($struct[$i]);
			}
		}
		else $struct = &$structure;

		// Build pattern to get indexes
		foreach($structure as &$value){
			$pattern .= ":".(isset($where[$value]) ? $where[$value] : '*');
		}
		echo("Pattern: $pattern\n");

		$found = [];

		$it = null;
		while($keys = $conn->scan($it, $pattern)){
		    foreach($keys as &$key){
		    	$indexes = explode(':', $key);

		    	// Create object from indexes
		    	$obj = [];
		    	for ($i = 0, $n = count($indexes) - 1; $i < $n; $i++) { 
		    		$obj[$structure[$i]] = &$indexes[$i + 1];
		    	}

		    	// Test if indexes meets conditions
		    	if($this->operation($obj, $where, false, $key)){
		    		$found[$key] = $obj;
		    		continue;
		    	}
		    }
		}

		if(count($found) === 0) return $found;

		// Return true if without fields
		if($withFields === false)
			return true;

		// Get obtained property from first result
		foreach($found as &$value){
			$have = array_keys($value);
			break;
		}
		$n = count($have);

		if(is_string($withFields)){
			$n--;
			$found_ = [];

			// Obtain required value only
			foreach ($found as &$value) {
				if(isset($value[$withFields])){
					$found_[] = $value[$withFields];
					continue;
				}

				die("ToDo: Get property from db");
			}
			return $found_;
		}

		elseif($n !== count($withFields)){
			die("ToDo: Get multiple property from db2");
		}

		// All data ready
		return $found;
	}

	public function &holes($tableName, $column, $length = 0, $offset = 0){
		// Do nothing
	}

	public function count($tableName, $where = false){

	}

	public function select($tableName, $select = '*', $where = false, $fetchUnique = false){

	}

	public function get($tableName, $select = '*', $where = false){
		$value = $this->doSearch($tableName, $where, $select);

		if(is_string($select)){
			if(count($value) === 1)
				return $value[0];
			return false;
		}

		return $value;
	}

	public function has($tableName, $where){

	}

	public function predict($tableName, $id = 'id', $where, &$cache = false){
		// Do nothing
	}

	public function &isEmpty($tableName, $columns, $where){
		// Do nothing
	}

	public function delete($tableName, $where){

	}

	public function insert($tableName, $object, $getInsertID = false){

	}

	public function update($tableName, $object, $where = false){

	}

	public function drop($tableName){

	}
}