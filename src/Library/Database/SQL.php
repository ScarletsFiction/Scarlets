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

		$this->debug = &$options['debug'];
		$this->table_prefix = $options['database'];
		if(isset($options['table_prefix']) && $options['table_prefix'] !== '')
			$this->table_prefix .= ".$options[table_prefix]";

		// Try to connect
		try{
			$this->connection = new PDO("$options[driver]:dbname=$options[database];host=$options[host];port=$options[port];charset=$options[charset]", $options['user'], $options['password']);
			$this->connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			trigger_error($e->getMessage());
		}
	}

	public function change($options){
		$this->table_prefix = $options['database'];
		$this->database = $options['database'];
		if(isset($options['table_prefix']) && $options['table_prefix'] !== '')
			$this->table_prefix .= ".$options[table_prefix]";
	}

	// SQLQuery
	private $queryRetry = false;
	public function query($query, $arrayData){
		if($this->debug)
			$this->lastQuery = [$query, $arrayData];

		try{
			$statement = $this->connection->prepare($query);
			$result = $statement->execute($arrayData);
		} catch(PDOException $e){
			$msg = $e->getMessage();
			if($msg){
				if(strpos($msg, 'Table') !== false && strpos($msg, 'doesn\'t exist') !== false){
					$tableName = explode("$this->table_prefix.", $msg)[1];
					$tableName = "$this->table_prefix.".explode('\'', $tableName)[0];

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
	private function validateText($text){
		return '`'.preg_replace('/[^a-zA-Z0-9_\.]+/m', '', $text).'`';
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
		$specialList = ['order', 'limit'];

		for($i = 0; $i < count($columns); $i++){
			$value = $object[$columns[$i]];

			$matches = $this->extractSpecial($columns[$i]);
			
			$check = strtolower($matches[0]);
			if($check==='and' || $check==='or') continue;
			if(!$children && in_array($check, $specialList) !== false) continue;

			if(isset($matches[1])){
				if(in_array($matches[1], ['>', '>=', '<', '<=']))
				{
					if(!is_nan($value))
					{
						$wheres[] = $this->validateText($matches[0]) . ' ' . $matches[1] . ' ?';
						$objectData[] = $value;
						continue;
					}
					else {
						trigger_error('SQL where: value of ' . $this->validateText($matches[0]) . ' is non-numeric and can\'t be accepted');
					}
				}
				else if($matches[1] === '!')
				{
					$type = gettype($value);
					if(!$type)
						$wheres[] = $this->validateText($matches[0]) . ' IS NOT NULL';
					else{
						if($type === 'array'){
							if(empty($value)) trigger_error("'$matches[0]' array couldn't be empty");
							
							$temp = [];
							for ($a = 0; $a < count($value); $a++) {
								$temp[] = '?';
							}
							$wheres[] = $this->validateText($matches[0]) . ' NOT IN ('. implode(', ', $temp) .')';
							$objectData = array_merge($objectData, $value);
						}

						else if($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
							$wheres[] = $this->validateText($matches[0]) . ' != ?';
							$objectData[] = $value;
						}

						else
							trigger_error('SQL where: value of' . $this->validateText($matches[0]) . ' with type ' . $type . ' can\'t be accepted');
					}
				}
				else if ($matches[1] === '~' || $matches[1] === '!~')
				{
					if(gettype($value) !== 'array'){
						$value = [$value];
					}

					$likes = [];
					for ($a = 0; $a < count($value); $a++) {
						$likes[] = $this->validateText($matches[0]) . ($matches[1] === '!~' ? ' NOT' : '') . ' LIKE ?';
						if(strpos($value[$a], '%') === false) $value[$a] = "%$value[$a]%";
						$objectData[] = $value[$a];
					}

                    $wheres[] = '('.implode(' OR ', $likes).')';
				}
			} else {
				$type = gettype($value);
				if(!$type)
					$wheres[] = $this->validateText($matches[0]) . ' IS NULL';
				else{
					if($type === 'array'){
						if(empty($value)) trigger_error("'$matches[0]' array couldn't be empty");

						$temp = [];
						for ($a = 0; $a < count($value); $a++) {
							$temp[] = '?';
						}
						$wheres[] = $this->validateText($matches[0]) . ' IN ('. implode(', ', $temp) .')';
						$objectData = array_merge($objectData, $value);
					}

					else if($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
						$wheres[] = $this->validateText($matches[0]) . ' = ?';
						$objectData[] = $value;
					}

					else
						trigger_error('SQL where: value ' . $this->validateText($matches[0]) . ' with type ' . $type . ' can\'t be accepted');
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
				$stack[] = $this->validateText($columns[$i]) . ' ' . $order;
			}

			$options = "$options ORDER BY " . implode(', ', $stack);
		}
		if(isset($object['LIMIT'])){
			if(!is_array($object['LIMIT']) && !is_nan($object['LIMIT']))
				$options = "$options LIMIT $object[LIMIT]";

			else if(!is_nan($object['LIMIT'][0]) && !is_nan($object['LIMIT'][1]))
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
	 * Find auto_increment holes in the indexed column
	 * 
	 * @param  string  $tableName [description]
	 * @param  string  $column    [description]
	 * @param  integer $scan      [description]
	 * @param  integer $jumps     [description]
	 * @return array              [description]
	 */
	public function &holes($tableName, $column, $scan = 1000, $jumps = 0){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		if(!is_numeric($scan) || !is_numeric($jumps))
			trigger_error('`Limit` or `Jumps` must be numeric value');

		$ids = $this->query('SELECT '.$this->validateText($column).' FROM '.$this->validateTable($tableName).' ORDER BY '.$this->validateText($column)." DESC LIMIT $jumps,$scan", [])->fetchAll(\PDO::FETCH_COLUMN);
		
		$last = $this->query('SELECT MAX('.$this->validateText($column).') FROM '.$this->validateTable($tableName), [])->fetchColumn(0);
		$last = $last - $jumps;

		if($last > $scan)
			$scan = $last - $scan;
		else $scan = 0;

		$missing = [];
		$ids = array_flip($ids);

		for ($i = $last - 1; $scan < $i; $i--) { 
			if(!isset($ids[$i]))
				$missing[] = $i;
		}

		return $missing;
	}

	public function createTable($tableName, $columns)
	{
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		$columns_ = array_keys($columns);
		for($i = 0; $i < count($columns_); $i++){
			if(gettype($columns[$columns_[$i]]) === 'array')
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper(implode(' ', $columns[$columns_[$i]]));
			else
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper(strval($columns[$columns_[$i]]));
		}
		$query = 'CREATE TABLE IF NOT EXISTS '.$this->validateTable($tableName).' ('.implode(', ', $columns_).')';

		return $this->query($query, []);
	}

	public function count($tableName, $where=false){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		$wheres = $this->makeWhere($where);
		$query = 'SELECT COUNT(1) FROM ' . $this->validateTable($tableName) . $wheres[0];
		
		return $this->query($query, $wheres[1])->fetchColumn(0);
	}

	public function select($tableName, $select = '*', $where = false, $fetchUnique = false){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";
		$wheres = $this->makeWhere($where);

		if(is_array($select)){
			$column = [];
			for($i = 0; $i < count($select); $i++){
				$column[] = $this->validateText($select[$i]);
			}
			$select_ = implode(',', $column);
		}
		elseif($select !== '*')
			$select_ = $this->validateText($select);
		
		$query = "SELECT $select_ FROM " . $this->validateTable($tableName) . $wheres[0];

		if(is_array($select) || $select === '*'){
			if($fetchUnique)
				return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_UNIQUE);
			return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $this->query($query, $wheres[1])->fetchAll(\PDO::FETCH_COLUMN);
	}

	public function get($tableName, $select = '*', $where = false){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);

		if(is_array($select)){
			$column = [];
			for($i = 0; $i < count($select); $i++){
				$column[] = $this->validateText($select[$i]);
			}
			$select_ = implode(',', $column);
		}
		elseif($select !== '*')
			$select_ = $this->validateText($select);
		
		$query = "SELECT $select_ FROM " . $this->validateTable($tableName) . $wheres[0];

		if(is_array($select) || $select === '*')
			return $this->query($query, $wheres[1])->fetch(\PDO::FETCH_ASSOC);
		return $this->query($query, $wheres[1])->fetchColumn(0);
	}

	public function has($tableName, $where){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);
		$query = 'SELECT 1 FROM ' . $this->validateTable($tableName) . $wheres[0];
		return $this->query($query, $wheres[1])->fetchColumn(0) === 1;
	}

	// Only avaiable for string columns
	public function &isEmpty($tableName, $columns, $where){
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

		$where['LIMIT'] = 1;
		$wheres = $this->makeWhere($where);
		
		$selectQuery = '';
		for ($i=0; $i < count($columns); $i++) { 
			$selectQuery .= ','.$this->validateText($columns[$i])." = '' AS '$i'";
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
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

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
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";

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
			$objectName[] = $this->validateText($columns[$i]);
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
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";
		$wheres = $this->makeWhere($where);

		$objectName = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$special = $this->extractSpecial($columns[$i]);
			$tableEscaped = $this->validateText($special[0]);

			if(count($special) === 1)
				$objectName[] = "$tableEscaped = ?";
			else {
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
		if($this->table_prefix !== '') $tableName = "$this->table_prefix.$tableName";
		
		return $this->query('DROP TABLE ' . $this->validateTable($tableName), []);
	}
}