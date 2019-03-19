<?php

/*
---------------------------------------------------------------------------
| SFDatabase SQL
---------------------------------------------------------------------------
|
| This library can help you build SQL query from PHP script. It's also
| validate every column and table text, so you don't need to worry
| about SQL injection.
|
*/
namespace Scarlets\Library\Database;
use \PDO;
use \PDOException;

class SQL{
	public $connection;
	public $debug = false;
	public $type = 'SQL';
	private $transactionCounter = 0;
	private $database = '';
	private $table_prefix = '';
	private $driver = '';

	// When debugging is enabled, this will have value
	public $lastQuery = false;

	public function __construct($options){
		// Default options
		if(!isset($options['host'])) $options['host'] = 'localhost';
		if(!isset($options['user'])) $options['user'] = 'root';
		if(!isset($options['port'])) $options['port'] = '3306';
		if(isset($options['username'])) $options['user'] = $options['username'];
		if(!isset($options['password'])) $options['password'] = '';
		if(!isset($options['driver'])) $options['driver'] = 'mysql';
		if(!isset($options['charset'])) $options['charset'] = 'utf8';
		if(!isset($options['debug'])) $options['debug'] = false;
		if(!isset($options['database'])) trigger_error('Database name was not specified');
		$this->database = $options['database'];
		$this->driver = $options['driver'];

		$this->debug = &$options['debug'];
		$this->table_prefix = "$options[database].";
		if(isset($options['table_prefix']) && $options['table_prefix'] !== '')
			$this->table_prefix .= "$options[table_prefix].";

		// Try to connect
		try{
			if($options['driver'] === 'sqlite')
				$this->connection = new PDO($options['host']);
			else 
				$this->connection = new PDO("$options[driver]:dbname=$options[database];host=$options[host];port=$options[port]", $options['user'], $options['password']);
			
			$this->connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			trigger_error($e->getMessage());
		}

		if($options['driver'] !== 'mysql')
			$this->escapes = '"';

		$query = "SET NAMES '$options[charset]'";
		if($this->type === 'mysql' && isset($options[ 'collation' ]))
			$query .= " COLLATE '$options[collation]}'";

		$this->query($query, []);
	}

	public function change($options = false){
		if($options === false){
			$this->table_prefix = '';
			return;
		}

		$this->table_prefix = "$options[database].";
		$this->database = $options['database'];
		if(isset($options['table_prefix']) && $options['table_prefix'] !== '')
			$this->table_prefix .= "$options[table_prefix].";
	}

	// SQLQuery
	private $queryRetry = false;
	public function query($query, $arrayData){
		if($this->debug){
			if($this->debug === 'log')
				\Scarlets\Log::message([$query, $arrayData]);
			else
				$this->lastQuery = [$query, $arrayData];
		}

		try{
			$statement = $this->connection->prepare($query);
			$result = $statement->execute($arrayData);
		} catch(PDOException $e){
			$msg = $e->getMessage();
			if($msg){
				if(strpos($msg, 'Table') !== false && strpos($msg, 'doesn\'t exist') !== false && $this->table_prefix !== ''){
					$tableName = explode($this->table_prefix, $msg);

					if(count($tableName) === 1){
						$tableName = explode("'", explode('Table \'', $msg)[1])[0];

						if(strpos($tableName, '.') !== false){
							$tableName = explode('.', $msg);
							$tableName = $tableName[count($tableName) - 1];
						}
					}
					else $tableName = $this->table_prefix.explode('\'', $tableName[1])[0];

					$ref = &$this->whenTableMissing;
					if(!$this->queryRetry && isset($ref[$tableName]) && is_callable($ref[$tableName])){
						$this->queryRetry = true;
						$ref[$tableName]();
						$this->queryRetry = false;
						return $this->query($query, $arrayData);
					}
				}
				trigger_error($msg);
			}
			return;
		}

		// $error = $statement->errorInfo();
		return $statement;
	}

    public function transaction($func)
    {
        $this->transactionCounter++;
        $this->connection->exec("SAVEPOINT trans$this->transactionCounter");
        $rollback = $func($this);
        if($rollback)
            $this->connection->exec("ROLLBACK TO trans$this->transactionCounter");
        else $this->connection->commit();
        $this->transactionCounter--;
    }

	// The code below could be similar with Javascript version
	// But the PHP version doesn't include preprocessData
	private $escapes = '`';
	private function validateColumn($text){
		return $this->escapes.preg_replace('/[^a-zA-Z0-9_\.]+/m', '', $text).$this->escapes;
	}

	private function validateTable($table){
		return preg_split('/[^a-zA-Z0-9_\.]/', $table, 2)[0];
	}

	private function extractSpecial($field){
		$field = explode('#', $field)[0];
		if(strpos($field, '[') === false) return [$field];
		return explode('[', str_replace([']', '"', '\'', '`'], '', $field));
	}
	
	/* columnName[operator]
		~ [Like]
		! [Not]
		without operator (Equal)
		>= or > (More than)
		<= or < (Lower than)
	*/
	// {('AND', 'OR'), 'ORDER':{columnName:'ASC', 'DESC'}, 'LIMIT':[startIndex, rowsLimit]}

	// ex: ['AND'=>['id'=>12, 'OR'=>['name'=>'myself', 'name'=>'himself']], 'LIMIT'=>1]
		// Select one where (id == 12 && (name == 'myself' || name == 'himself'))
	private function makeWhere($object, $comparator=false, $children=false){
		if(!$object) return ['', []];
		$wheres = [];

		$objectData = [];
		$columns = array_keys($object);
		$defaultConditional = ' AND ';
		$specialList = ['ORDER', 'LIMIT'];

		for($i = 0; $i < count($columns); $i++){
			$value = $object[$columns[$i]];

			$matches = $this->extractSpecial($columns[$i]);
			
			$check = strtoupper($matches[0]);
			if($check === 'AND' || $check === 'OR') continue;
			if(!$children && in_array($check, $specialList) !== false) continue;

			if(isset($matches[1])){
				if(in_array($matches[1], ['>', '>=', '<', '<=']))
				{
					if(!is_nan($value))
					{
						$wheres[] = $this->validateColumn($matches[0]) . ' ' . $matches[1] . ' ?';
						$objectData[] = $value;
						continue;
					}
					else
						trigger_error('SQL where: value of ' . $this->validateColumn($matches[0]) . ' is non-numeric and can\'t be accepted');
				}
				elseif($matches[1] === '!')
				{
					$type = gettype($value);
					if(!$type)
						$wheres[] = $this->validateColumn($matches[0]) . ' IS NOT NULL';
					else{
						if($type === 'array'){
							if(empty($value)) trigger_error("'$matches[0]' array couldn't be empty");
							else {
								$temp = [];
								foreach ($value as &$xx) {
									$temp[] = '?';
								}

								$wheres[] = $this->validateColumn($matches[0]) . ' NOT IN ('. implode(', ', $temp) .')';
								$objectData = array_merge($objectData, $value);
							}
						}

						elseif($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
							$wheres[] = $this->validateColumn($matches[0]) . ' != ?';
							$objectData[] = $value;
						}

						else
							trigger_error('SQL where: value of' . $this->validateColumn($matches[0]) . ' with type ' . $type . ' can\'t be accepted');
					}
				}
				elseif(substr($matches[1], -1) === '~')
				{
					if(gettype($value) !== 'array')
						$value = [$value];

					$OR = strpos($matches[1], '&') === false ? ' OR ' : ' AND ';
					$NOT = strpos($matches[1], '!') === 0 ? ' NOT' : '';

					$likes = [];
					for ($a = 0; $a < count($value); $a++) {
						$likes[] = $this->validateColumn($matches[0]) . "$NOT LIKE ?";
						if(strpos($value[$a], '%') === false) $value[$a] = "%$value[$a]%";
						$objectData[] = $value[$a];
					}

                    $wheres[] = '('.implode($OR, $likes).')';
				}
				elseif(substr($matches[1], -1) === ','){
					$NOT = strpos($matches[1], '!') === 0 ? ' NOT' : '';
					$OR = strpos($matches[1], '&') === false ? ' OR ' : ' AND ';

					if(gettype($value) === 'array'){
						if(count($value) > 2 && $OR === ' OR '){ // Optimize performance with regexp
							$value = '('.implode('|', $value).')';

							if($NOT !== '' && $this->driver === 'pgsql')
								$like = ' !~ ?';
							else $like = " NOT REGEXP ?";

		                	$wheres[] = $this->validateColumn($matches[0]).$like;
							$objectData[] = ",$value,";
						}
						else{
							$tempValue = [];
							for ($i=0; $i < count($value); $i++) { 
								$tempValue[] = "$NOT LIKE ?";
								$objectData[] = ",$value[$i],";
							}
							$wheres[] = implode($OR, $tempValue);
						}
					}
					else{
						$wheres[] = $this->validateColumn($matches[0])."$NOT LIKE ?";
						$objectData[] = ",$value,";
					}
				}

				else { // Special feature
					$matches[1] = strtoupper($matches[1]);

					if(strpos($matches[1], 'LENGTH') !== false){
						if(preg_match('/[<>=]+/', $matches[1], $op))
							$op = $op[0];
						else $op = '=';

						$wheres[] = "CHAR_LENGTH(".$this->validateColumn($matches[0]).") $op ?";
						$objectData[] = $value;
					}

					elseif($matches[1] === 'REGEXP'){
						if(gettype($value) === 'array')
							trigger_error('SQL where: value of' . $this->validateColumn($matches[0]) . ' must be a string');

	                    $wheres[] = $this->validateColumn($matches[0]).($this->driver === 'pgsql' ? ' ~ ' : ' REGEXP ').'?';
	                    $objectData[] = $value;
					}
				}
			}

			else {
				$type = gettype($value);
				if(!$type)
					$wheres[] = $this->validateColumn($matches[0]) . ' IS NULL';
				else{
					if($type === 'array'){
						if(empty($value)) trigger_error("'$matches[0]' array couldn't be empty");
						else{
							$temp = [];
							foreach ($value as &$xx) {
								$temp[] = '?';
							}

							$wheres[] = $this->validateColumn($matches[0]) . ' IN ('. implode(', ', $temp) .')';
							$objectData = array_merge($objectData, $value);
						}
					}

					elseif($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
						$wheres[] = $this->validateColumn($matches[0]) . ' = ?';
						$objectData[] = $value;
					}

					else
						trigger_error('SQL where: value ' . $this->validateColumn($matches[0]) . ' with type ' . $type . ' can\'t be accepted');
				}
			}
		}

		for ($i = 0; $i < count($columns); $i++) {
			if($columns[$i]==='ORDER'||$columns[$i]==='LIMIT')
                continue;

			$test = explode('AND', $columns[$i]);
			$haveRelation = false;
			if(count($test) === 2 && $test[0] === ''){
				$defaultConditional = ' AND ';
				$haveRelation = true;
			}
			else{
				$test = explode('OR', $columns[$i]);
				if(count($test) === 2 && $test[0] === ''){
					$defaultConditional = ' OR ';
					$haveRelation = true;
				}
			}
			
			if($haveRelation){
				$childs = $this->makeWhere($object[$columns[$i]], $defaultConditional, true);
				$wheres[] = "($childs[0])";
				$objectData = array_merge($objectData, $childs[1]);
			}
		}

		$options = '';
		if(isset($object['ORDER'])){
			if(is_string($object['ORDER']))
				$object['ORDER'] = [$object['ORDER']=>'ASC'];

			$columns = array_keys($object['ORDER']);
			$stack = [];
			for($i = 0; $i < count($columns); $i++){
				$order = strtoupper($object['ORDER'][$columns[$i]]);
				if($order !== 'ASC' && $order !== 'DESC') continue;
				$stack[] = $this->validateColumn($columns[$i]) . ' ' . $order;
			}

			$options = "$options ORDER BY " . implode(', ', $stack);
		}
		if(isset($object['LIMIT'])){
			if(!is_array($object['LIMIT']) && !is_nan($object['LIMIT']))
				$options = "$options LIMIT $object[LIMIT]";

			elseif(!is_nan($object['LIMIT'][0]) && !is_nan($object['LIMIT'][1]))
				$options = "$options LIMIT " . $object['LIMIT'][1] . ' OFFSET ' . $object['LIMIT'][0];
		}
		
		$where_ = '';
		if(count($wheres)!==0){
			if(!$children)
				$where_ = ' WHERE ';
			$where_ = $where_ . implode($comparator ? $comparator : $defaultConditional, $wheres);
		}

		return [$where_ . $options, $objectData];
	}

	private $whenTableMissing = [];
	public function onTableMissing($table, $func){
		$this->whenTableMissing["$this->database.$table"] = $func;
	}

	/**
	 * Find auto_increment holes in the indexed column.
	 * Will return empty array if not found.
	 * And return false if out of range.
	 * 
	 * @param  string  $tableName [description]
	 * @param  string  $column    [description]
	 * @param  integer $length      [description]
	 * @param  integer $offset     [description]
	 * @return array              [description]
	 */
	public function &holes($tableName, $column, $length = 0, $offset = 0){
		if(!is_numeric($length) || !is_numeric($offset))
			trigger_error('`length` or `offset` must be numeric value');

		if($length !== 0){
			$total = $this->query('SELECT MAX('.$this->validateColumn($column).') FROM '.$this->validateTable($this->table_prefix.$tableName), [])->fetchColumn(0);

			if($total <= $offset)
				return false;

			$ids = $this->select($tableName, $column, [
				'ORDER'=>[$column=>'ASC'],
				'LIMIT'=>[$offset, $length]
			]);

			if(count($ids) === 0)
				return false;
			$first = $ids[0];
		}
		else $ids = $this->select($tableName, $column);

		$ids = array_flip($ids);
		$missing = [];

		if($length !== 0){
			for ($i = $first, $n = $first + $length; $i < $n; $i++) { 
				if(!isset($ids[$i]))
					$missing[] = $i;
			}
		}
		else{
			for ($i = 1, $n = count($ids); $i < $n; $i++) { 
				if(!isset($ids[$i]))
					$missing[] = $i;
			}
		}

		return $missing;
	}

	public function createTable($tableName, $columns)
	{
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		$columns_ = array_keys($columns);
		for($i = 0; $i < count($columns_); $i++){
			if(gettype($columns[$columns_[$i]]) === 'array')
				$columns_[$i] = $this->validateColumn($columns_[$i]).' '.strtoupper(implode(' ', $columns[$columns_[$i]]));
			else
				$columns_[$i] = $this->validateColumn($columns_[$i]).' '.strtoupper(strval($columns[$columns_[$i]]));
		}
		$query = 'CREATE TABLE IF NOT EXISTS '.$this->validateTable($tableName).' ('.implode(', ', $columns_).')';

		return $this->query($query, []);
	}

	public function count($tableName, $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		$wheres = $this->makeWhere($where);
		$query = 'SELECT COUNT(1) FROM ' . $this->validateTable($tableName) . $wheres[0];
		
		return $this->query($query, $wheres[1])->fetchColumn(0);
	}

	public function select($tableName, $select = '*', $where = false, $fetchUnique = false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;
		$wheres = $this->makeWhere($where);

		$isColumns = true;
		if(is_array($select)){
			$column = [];
			for($i = 0; $i < count($select); $i++){
				$column[] = $this->validateColumn($select[$i]);
			}
			$select = implode(',', $column);
		}
		elseif($select !== '*'){
			$select = $this->validateColumn($select);
			$isColumns = false;
		}

		$query = "SELECT $select FROM " . $this->validateTable($tableName) . $wheres[0];

		if($isColumns === true || $select === '*'){
			if($fetchUnique)
				return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_UNIQUE);
			return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_COLUMN);
	}

	public function get($tableName, $select = '*', $where = false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);

		$isColumns = true;
		if(is_array($select)){
			$column = [];
			for($i = 0; $i < count($select); $i++){
				$column[] = $this->validateColumn($select[$i]);
			}
			$select = implode(',', $column);
		}
		elseif($select !== '*'){
			$select = $this->validateColumn($select);
			$isColumns = false;
		}

		$query = "SELECT $select FROM " . $this->validateTable($tableName) . $wheres[0];

		if($isColumns === true || $select === '*')
			return $this->query($query, $wheres[1])->fetch(\PDO::FETCH_ASSOC);
		return $this->query($query, $wheres[1])->fetchColumn(0);
	}

	public function has($tableName, $where){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);
		$query = 'SELECT 1 FROM ' . $this->validateTable($tableName) . $wheres[0];
		return $this->query($query, $wheres[1])->fetchColumn(0) === 1;
	}

	public function predict($tableName, $id = 'id', $where, &$cache = false){
		$column = '';
		$value = '';
		foreach ($where as $key => $val) {
			if(strpos($key, '[%]') !== false){
				$column = $key;
				$value = $val;
				break;
			}
		}

		$strlen = strlen($value);
		if($strlen < 2) // Protect system from meaningless test
			return [];

		unset($where[$column]);
		$value = strtolower($value);
		$column = substr($column, 0, -3);

		// On interactive CLI mode, the data must be cached
		if(\Scarlets::$interactiveCLI === false)
			$where[$column.'[length(>)]'] = $strlen;

		$obtained = [];
		$lastLow = 1;
		$lastLowID = 0;

		// Get the data if cache is not available
		if($cache === false)
			$cache = $this->select($tableName, [$id, $column], $where);

		$z = 0;
		foreach ($cache as &$ref) {
			$text = strtolower($ref[$column]);
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
				if($val[1] < $lastLow && $pendingScore === 0){
					$lastLow = $score;
					$lastLowID = $z;
				}
				$z++;
			}

			$obtained[] = [$ref[$id], $pendingScore ?: $score];
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

	// Only avaiable for string columns
	public function &isEmpty($tableName, $columns, $where){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);
		
		$selectQuery = '';
		for ($i=0; $i < count($columns); $i++) { 
			$selectQuery .= ','.$this->validateColumn($columns[$i])." = '' AS '$i'";
		}

		$query = 'SELECT '.substr($selectQuery, 1).' FROM ' . $this->validateTable($tableName) . $wheres[0];
		$obtained = $this->query($query, $wheres[1])->fetchColumn();

		if($obtained === false) return $obtained;

		$data = [];
		for ($i=0; $i < count($columns); $i++) { 
			$data[$columns[$i]] = $obtained[$i] === 1;
		}

		return $data;
	}

	public function delete($tableName, $where){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		if($where){
			$wheres = $this->makeWhere($where);
			$query = 'DELETE FROM ' . $this->validateTable($tableName) . $wheres[0];
			return $this->query($query, $wheres[1])->rowCount();
		}
		else{
			$query = 'TRUNCATE TABLE ' . $this->validateTable($tableName);
			return $this->query($query, [])->rowCount();
		}
	}

	public function insert($tableName, $object, $getInsertID = false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;

		// Multiple insert
		$multiple = false;
		if(isset($object[0]) && is_array($object[0])){
			$multiple = $object;

			// Take a sample
			$object = array_shift($multiple);
		}

		$objectName = [];
		$objectName_ = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$objectName[] = $this->validateColumn($columns[$i]);
			$objectName_[] = '?';

			$objectData[] = $object[$columns[$i]];
		}
		$query = 'INSERT INTO ' . $this->validateTable($tableName) . ' (' . implode(', ', $objectName) . ') VALUES (' . implode(', ', $objectName_) . ')';

		if($getInsertID === false){
			if($multiple != false){ // Check if not false or not empty
				$mask = '('.implode(', ', $objectName_).')';
				foreach($multiple as $row){
					$objectData[] = &$row;
					$query .= ",$mask";
				}
			}
			return $this->query($query, $objectData);
		}

		// The script below will only be executer for taking all the insert ID
		$statement = $this->query($query, $objectData);
		$lastInsertId = $this->connection->lastInsertId();

		// Multiple insert
		if($multiple !== false){ // Check if not false
			$lastInsertId = [$lastInsertId];
			foreach($multiple as $row){
				$statement->execute($row);
				$lastInsertId[] = $this->connection->lastInsertId();
			}
		}

		return $lastInsertId;
	}

	public function update($tableName, $object, $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;
		$wheres = $this->makeWhere($where);

		$objectName = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$special = $this->extractSpecial($columns[$i]);
			$tableEscaped = $this->validateColumn($special[0]);

			if(count($special) === 1)
				$objectName[] = "$tableEscaped = ?";
			else {
				// Add value into array
				if($special[1] === ',++'){
					$objectName[] = "$tableEscaped = CONCAT($tableEscaped, ?)";
					if(is_array($object[$columns[$i]]) === true)
						$objectData[] = implode(',', $object[$columns[$i]]).',';
					else $objectData[] = $object[$columns[$i]].',';
					continue;
				}

				// Remove value from array
				elseif($special[1] === ',--'){
					if(is_array($object[$columns[$i]]) === true){
						$replacer = $this->connection->quote(',('.implode('|', $object[$columns[$i]]).'),');
						$objectName[] = "$tableEscaped = REGEXP_REPLACE($tableEscaped, $replacer, ',')";
					}
					else {
						$objectName[] = "$tableEscaped = REPLACE($tableEscaped, ?, ',')";
						$objectData[] = ','.$object[$columns[$i]].',';
					}
					continue;
				}

				if(is_string($object[$columns[$i]])){
					// Append
					if($special[1] === 'append')
						$objectName[] = "$tableEscaped = CONCAT($tableEscaped, ?)";

					// Prepend
					elseif($special[1] === 'prepend')
						$objectName[] = "$tableEscaped = CONCAT(?, $tableEscaped)";

					else trigger_error("No operation for '$special[1]'");
				}

				elseif(is_array($object[$columns[$i]])){
					// Replace
					if($special[1] === 'replace')
						$objectName[] = "$tableEscaped = REPLACE($tableEscaped, ?, ?)";

					// Wrap
					elseif($special[1] === 'wrap')
						$objectName[] = "$tableEscaped = CONCAT(?, $tableEscaped, ?)";

					else trigger_error("No operation for '$special[1]'");

					$objectData[] = $object[$columns[$i]][0];
					$objectData[] = $object[$columns[$i]][1];
					continue;
				}

				// Math
				elseif(in_array($special[1], ['*', '-', '/', '%', '+']))
					$objectName[] = "$tableEscaped = $tableEscaped $special[1] ?";

				else trigger_error("No operation for '$special[1]'");
			}

			$objectData[] = $object[$columns[$i]];
		}
		$query = 'UPDATE ' . $this->validateTable($tableName) . ' SET ' . implode(', ', $objectName) . $wheres[0];

		return $this->query($query, array_merge($objectData, $wheres[1]))->rowCount();
	}

	public function drop($tableName){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.$tableName;
		
		return $this->query('DROP TABLE ' . $this->validateTable($tableName), []);
	}
}