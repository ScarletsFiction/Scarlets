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
| ToDo: OFFSET, ORDER
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

			// Recursion again
			if($prop === 'AND')
				$operationCondition = $this->operation($data, $rule, false, $key);

			// Recursion again
			elseif($prop === 'OR')
				$operationCondition = $this->operation($data, $rule, true, $key);

			else{
				// Get missing data first if it was not found
				if(isset($data[$prop]) === false)
					$data[$prop] = $this->conn->hget($key, $prop);

				if($oper === false){ // Equal to
					if(is_array($rule)){
						if(strpos($rule, $data[$prop]) === false)
							$operationCondition = false; // When nothing match
					}
					elseif($data[$prop] != $rule)
						$operationCondition = false; // When "not equal to"
				}

				elseif($oper === '!'){ // Not equal to
					if(is_array($rule)){
						if(strpos($rule, $data[$prop]) !== false)
							$operationCondition = false; // When something match
					}
					elseif($data[$prop] == $rule)
						$operationCondition = false; // When "equal to"
				}

				elseif($oper === '>'){ // Greater than
					if($data[$prop] <= $rule)
						$operationCondition = false; // When "lower or equal to"
				}

				elseif($oper === '>='){ // Greater or equal
					if($data[$prop] < $rule)
						$operationCondition = false; // When "lower than"
				}

				elseif($oper === '<'){ // Lower than
					if($data[$prop] >= $rule)
						$operationCondition = false; // When "more than or equal"
				}

				elseif($oper === '<='){ // Lower or equal
					if($data[$prop] > $rule)
						$operationCondition = false; // When "more than"
				}

				elseif($oper === '><'){ // Between 2 value
					if($data[$prop] <= $rule[0] || $data[$prop] >= $rule[1])
						$operationCondition = false; // When "not between 2 value or equal"
				}

				elseif($oper === '=><='){ // Between 2 value
					if($data[$prop] < $rule[0] || $data[$prop] > $rule[1])
						$operationCondition = false; // When "not between 2 value"
				}

				elseif($oper === '<>'){ // Not between than 2  value
					if($data[$prop] >= $rule[0] || $data[$prop] <= $rule[1])
						$operationCondition = false; // When "between 2 value or equal"
				}

				elseif($oper === '<=>'){ // Not between than 2  value
					if($data[$prop] > $rule[0] || $data[$prop] < $rule[1])
						$operationCondition = false; // When "between 2 value"
				}

				elseif(strpos($oper, '~') !== false){ // Data likes
					$likeCode = 1; // 1 = %value%, 2 = %value, 3 = value%
					$regexed = [];

					if(is_array($rule) === false)
						$rule = [$rule];

					foreach($rule as &$temp){
						if($temp[0] === '%' && substr($temp, -1) === '%'){
							$likeCode = 1;
							$temp = substr($temp, 1, -1);
						}

						elseif($temp[0] === '%'){
							$likeCode = 2;
							$temp = substr($temp, 1);
						}

						elseif(substr($temp, -1) === '%'){
							$likeCode = 3;
							$temp = substr($temp, 0, -1);
						}

						$temp = preg_replace('/[-\/\\^$*+?.()|[\]{}]/g', '\\$&', $temp);

						if($likeCode === 2)
							$temp = "$temp$";

						elseif($likeCode === 3)
							$temp = "^$temp";

						$regexed[] = $temp;
					}

					$exist = preg_match('/'.implode('|', $regexed).'/i', $data[$prop]) !== false;

					if(strpos($oper, '!') !== false) // Data not like
						if($exist) $operationCondition = false; // When "have match"

					elseif(!$exist) // Data like
						$operationCondition = false; // When "not match"
				}
			}

			if($ORCondition) // OR
				$currentCondition = $currentCondition || $operationCondition;

			elseif($operationCondition === false){ // AND
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

		$found = [];
		$LIMIT = $OFFSET = false;

		if(isset($where['LIMIT'])){
			if(is_array($where['LIMIT'])){
				$LIMIT = &$where['LIMIT'][1];
				$OFFSET = &$where['LIMIT'][0];
			}
			else $LIMIT = &$where['LIMIT'];

			$z = 0;
			unset($where['LIMIT']);
		}

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

		    		if($LIMIT !== false){
		    			$z++;
		    			if($LIMIT <= $z) break 2;
		    		}
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
		$where['LIMIT'] = 1;

		$value = $this->doSearch($tableName, $where, $select);

		if(is_string($select)){
			if(count($value) === 1)
				return $value[0];
			return $value;
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