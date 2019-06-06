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

				elseif($oper === '<>'){ // Not between than 2 value
					if($data[$prop] >= $rule[0] || $data[$prop] <= $rule[1])
						$operationCondition = false; // When "between 2 value or equal"
				}

				elseif(strpos($oper, '~') !== false){ // Data likes
					if(is_array($rule) === false)
						$rule = [$rule];

					$likeCode = 0; // 0 = %value%, 1 = %value, 2 = value%
					$like = [[],[],[]];
					$testData = strtolower($data[$prop]);

					foreach($rule as &$temp){
						// Escape symbol
						$temp = strtolower($temp);

						if($temp[0] === '%' && substr($temp, -1) === '%')
							$like[0][] = substr($temp, 1, -1);

						elseif($temp[0] === '%')
							$like[1][] = substr($temp, 1);

						elseif(substr($temp, -1) === '%')
							$like[2][] = substr($temp, 0, -1);

						else $like[0][] = $temp;
					}

					$exist = false;
					foreach ($like[0] as &$value) { // %value%
						if(stripos($testData, $value) !== false)
							$exist = true;
					}

					if($exist === false)
						foreach ($like[2] as &$value) {// value%
							if(stripos($testData, $value) === 0)
								$exist = true;
						}

					if($exist === false && count($like[1]) !== 0){
						$k = strlen($testData);
						foreach ($like[1] as &$value) {// %value
							if(stripos($testData, $value) === $k - strlen($value))
								$exist = true;
						}
					}

					if($oper[0] === '!') // Data not like
						$operationCondition = $exist === false; // Is not exist?

					else // Data like
						$operationCondition = $exist; // When "not match"
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

		// Return current result if without fields
		if($withFields === false)
			return $found;

		$found_ = [];
		if(is_string($withFields)){
			// Obtain required value only
			foreach ($found as &$value) {
				if(isset($value[$withFields])){
					$found_[] = &$value[$withFields];
					continue;
				}

				$found_[] = $this->conn->hget($key, $withFields);
			}

			return $found_;
		}

		// Get sample of one result and get missing fields
		$missing = [];
		foreach ($found as &$sample) {
			for($i = count($withFields)-1; $i >= 0; $i--){ 
				if(isset($sample[$withFields[$i]]) === false)
					$missing[] = &$withFields[$i];
			}

			break;
		}

		// Fill missing value
		if(count($missing) !== 0){
			foreach ($found as $key => &$value) {
				$value = array_replace($value, $this->conn->hmget($key, $missing));
			}
		}

		// Obtain required value only
		foreach ($found as &$temp) {
			$row = [];
			foreach ($withFields as &$field) {
				$row[$field] = isset($temp[$field]) ? $temp[$field] : null;
			}
			$found_[] = $row;
		}

		return $found_;
	}

	public function &holes($tableName, $column, $length = 0, $offset = 0){
		// Do nothing
	}

	public function count($tableName, $where = false){
		return count($this->doSearch($tableName, $where));
	}

	public function select($tableName, $select = '*', $where = false, $fetchUnique = false){
		if(is_string($select))
			$select = [$select];
		$value = $this->doSearch($tableName, $where, $select);
		return $value;
	}

	public function get($tableName, $select = '*', $where = false){
		$where['LIMIT'] = 1;

		$value = $this->doSearch($tableName, $where, $select);

		if(is_string($select)){
			if(count($value) === 1)
				return $value[0];
			return $value;
		}

		return $value[0];
	}

	public function has($tableName, $where){
		$where['LIMIT'] = 1;
		return count($this->doSearch($tableName, $where)) === 1;
	}

	public function predict($tableName, $id = 'id', $where, &$cache = false){
		// Do nothing
	}

	public function &isEmpty($tableName, $columns, $where){
		// Do nothing
	}

	public function delete($tableName, $where){
		$found = $this->doSearch($tableName, $where);
		foreach ($found as $key => &$value) {
		    $this->conn->del($key);
		}
		return count($found);
	}

	public function &insert($tableName, $object){
		$conn = &$this->conn;
		$struct = &$this->structure[$tableName];

		// Check if multiple
		$multiple = false;
		if(isset($object[0]) === false){
			$object = [$object];
			$multiple = [];
		}

		foreach ($object as &$row) {
			if(isset($row[$struct[0]]) === false)
				$insertID = $conn->incr("$tableName:_internal_:auto_inc");
			else $insertID = &$row[$struct[0]];

			// Create key first
			$key = $tableName;
			foreach($struct as &$indexes){
				if(isset($row[$indexes]) === false)
					throw new \Exception("`$indexes` index value is missing", 1);

				$key .= ":$row[$indexes]";
				unset($row[$indexes]);
			}

			// Insert to database
			$conn->hmset($key, $row);

			if($multiple === false)
				return $insertID;

			$multiple[] = $insertID;
		}

		return $multiple;
	}

	public function update($tableName, $object, $where = false){
		$tableName_ = $tableName;
		$struct = &$this->structure[$tableName];
		$conn = &$this->conn;
		$found = $this->doSearch($tableName, $where);

		foreach ($found as $key => &$row) {
			$copy = array_replace([], $object);

			// Check first if the key would be renamed
			$keyRename = false;
			foreach ($row as $prop => &$val) {
				if(isset($copy[$prop])){
					$val = $copy[$prop];
					$keyRename = true;
					unset($copy[$prop]);
				}
			}

			// Change key first
			if($keyRename){
				$key_ = $tableName_;
				foreach ($struct as &$val) {
					$key_ .= ":".str_replace(':', '_', $row[$val]);
				}
				$conn->rename($key, $key_);
				$key = &$key_;
			}

			// Check if no other update needed
			if(count($copy) === 0) continue;

			// Update multiple hash value
			$conn->hmset($key, $copy);
		}

		return count($found);
	}

	public function drop($tableName){
		$it = null;
		$conn = &$this->conn;
		$tableName = "$tableName:*";

		$count = 0;
		while($keys = $conn->scan($it, $tableName)){
		    foreach($keys as &$key){
		    	$conn->del($key);
		    	$count++;
		    }
		}
		return $count;
	}
}