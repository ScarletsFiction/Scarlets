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
		if(!isset($options['database'])) trigger_error("Database name was not specified");
		$this->database = $options['database'];

		$this->debug = &$options['debug'];
		$this->table_prefix = $options['database'];
		if(isset($options['table_prefix']) && $options['table_prefix'] !== '')
			$this->table_prefix .= '.'.$options['table_prefix'];

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
			$this->table_prefix .= '.'.$options['table_prefix'];
	}

	// SQLQuery
	private $queryRetry = false;
	public function &query($query, $arrayData, $get = false){
		if($this->debug)
			$this->lastQuery = [$query, $arrayData];

		try{
			$statement = $this->connection->prepare($query);
			$result = $statement->execute($arrayData);
		} catch(PDOException $e){
			$msg = $e->getMessage();
			if($msg){
				if(strpos($msg, "Table") !== false && strpos($msg, "doesn't exist") !== false){
					$tableName = explode($this->table_prefix.'.', $msg)[1];
					$tableName = $this->table_prefix.'.'.explode("'", $tableName)[0];

					$ref = &$this->whenTableMissing;
					if(!$this->queryRetry && isset($ref[$tableName]) && is_callable($ref[$tableName])){
						$this->queryRetry = true;
						$ref[$tableName]();
						$this->queryRetry = false;
						$temp = $this->query($query, $arrayData, $get);
						return $temp;
					}
				}
				trigger_error($msg);
			}
		}

		// $error = $statement->errorInfo();

		if($get === 'rows')
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
		elseif($get === 'insertID')
			$result = $this->connection->lastInsertId();

		return $result;
	}

    public function transaction($func)
    {
        $this->transactionCounter++;
        $this->connection->exec('SAVEPOINT trans'.$this->transactionCounter);
        $rollback = $func($this);
        if($rollback)
            $this->connection->exec('ROLLBACK TO trans'.$this->transactionCounter);
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
	
	/* columnName[operator]
		~ [Like]
		! [Not]
		without operator (Equal)
		>= or > (More than)
		<= or < (Lower than)
	*/
	// {('AND', 'OR'), 'ORDER':{columnName:'ASC', 'DESC'}, 'LIMIT':[startIndex, rowsLimit]}

	// ex: ["AND"=>["id"=>12, "OR"=>["name"=>"myself", "name"=>"himself"]], "LIMIT"=>1]
		// Select one where (id == 12 && (name == "myself" || name == "himself"))
	private function makeWhere($object, $comparator=false, $children=false){
		if(!$object) return ['', []];
		$wheres = [];

		$objectData = [];
		$columns = array_keys($object);
		$defaultConditional = ' AND ';
		$specialList = ['order', 'limit'];

		for($i = 0; $i < count($columns); $i++){
			$value = $object[$columns[$i]];

			preg_match('/([a-zA-Z0-9_\.]+)(\[(\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\])?/', $columns[$i], $matches);
			$check = strtolower($matches[1]);
			if($check==='and' || $check==='or') continue;
			if(!$children && in_array($check, $specialList) !== false) continue;

			if(isset($matches[3])){
				if(in_array($matches[3], ['>', '>=', '<', '<=']))
				{
					if(!is_nan($value))
					{
						$wheres[] = $this->validateText($matches[1]) . ' ' . $matches[3] . ' ?';
						$objectData[] = $value;
						continue;
					}
					else {
						trigger_error('SQL where: value of ' . $this->validateText($matches[1]) . ' is non-numeric and can\'t be accepted');
					}
				}
				else if($matches[3] === '!')
				{
					$type = gettype($value);
					if(!$type)
						$wheres[] = $this->validateText($matches[1]) . ' IS NOT NULL';
					else{
						if($type === 'array'){
							$temp = [];
							for ($a = 0; $a < count($value); $a++) {
								$temp[] = '?';
							}
							$wheres[] = $this->validateText($matches[1]) . ' NOT IN ('. implode(', ', $temp) .')';
							$objectData = array_merge($objectData, $value);
						}

						else if($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
							$wheres[] = $this->validateText($matches[1]) . ' != ?';
							$objectData[] = $value;
						}

						else
							trigger_error('SQL where: value of' . $this->validateText($matches[1]) . ' with type ' . $type . ' can\'t be accepted');
					}
				}
				else if ($matches[3] === '~' || $matches[3] === '!~')
				{
					if(gettype($value) !== 'array'){
						$value = [$value];
					}

					$likes = [];
					for ($a = 0; $a < count($value); $a++) {
						$likes[] = $this->validateText($matches[1]) . ($matches[3] === '!~' ? ' NOT' : '') . ' LIKE ?';
						if(strpos($value[$a], '%') === false) $value[$a] = '%'.$value[$a].'%';
						$objectData[] = $value[$a];
					}

                    $wheres[] = '('.implode(' OR ', $likes).')';
				}
			} else {
				$type = gettype($value);
				if(!$type)
					$wheres[] = $this->validateText($matches[1]) . ' IS NULL';
				else{
					if($type === 'array'){
						$temp = [];
						for ($a = 0; $a < count($value); $a++) {
							$temp[] = '?';
						}
						$wheres[] = $this->validateText($matches[1]) . ' IN ('. implode(', ', $temp) .')';
						$objectData = array_merge($objectData, $value);
					}

					else if($type==='integer' || $type==='double' || $type==='boolean' || $type==='string'){
						$wheres[] = $this->validateText($matches[1]) . ' = ?';
						$objectData[] = $value;
					}

					else
						trigger_error('SQL where: value ' . $this->validateText($matches[1]) . ' with type ' . $type . ' can\'t be accepted');
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
				$wheres[] = '('.$childs[0].')';
				$objectData = array_merge($objectData, $childs[1]);
			}
		}

		$options = '';
		if(isset($object['ORDER'])){
			$columns = array_keys($object['ORDER']);
			$stack = [];
			for($i = 0; $i < count($columns); $i++){
				$order = strtoupper($object['ORDER'][$columns[i]]);
				if($order !== 'ASC' && $order !== 'DESC') continue;
				$stack[] = $this->validateText($columns[$i]) . ' ' . $order;
			}
			$options = $options . ' ORDER BY ' . implode(', ', $stack);
		}
		if(isset($object['LIMIT'])){
			if(!is_array($object['LIMIT']) && !is_nan($object['LIMIT'])){
				$options = $options . ' LIMIT '. $object['LIMIT'];
			}
			else if(!is_nan($object['LIMIT'][0]) && !is_nan($object['LIMIT'][1])){
				$options = $options . ' LIMIT ' . $object['LIMIT'][1] . ' OFFSET ' . $object['LIMIT'][0];
			}
		}
		
		$where_ = '';
		if(count($wheres)!==0){
			if(!$children)
				$where_ = " WHERE ";
			$where_ = $where_ . implode($comparator ? $comparator : $defaultConditional, $wheres);
		}

		return [$where_ . $options, $objectData];
	}

	private $whenTableMissing = [];
	public function onTableMissing($table, $func){
		$this->whenTableMissing[$this->database.'.'.$table] = $func;
	}

	public function &createTable($tableName, $columns)
	{
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		$columns_ = array_keys($columns);
		for($i = 0; $i < count($columns_); $i++){
			if(gettype($columns[$columns_[$i]]) === 'array')
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper(implode(' ', $columns[$columns_[$i]]));
			else
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper(strval($columns[$columns_[$i]]));
		}
		$query = 'CREATE TABLE IF NOT EXISTS '.$this->validateTable($tableName).' ('.implode(', ', $columns_).')';

		return $this->query($query, [], 'create');
	}

	public function count($tableName, $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		$wheres = $this->makeWhere($where);
		$query = "SELECT COUNT(1) FROM " . $this->validateTable($tableName) . $wheres[0];
		
		return $this->query($query, $wheres[1], 'rows')[0]['COUNT(1)'];
	}

	public function &select($tableName, $select='*', $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		$select_ = $select;
		if($select!=='*')
			for($i = 0; $i < count($select_); $i++){
				$select_[$i] = $this->validateText($select_[$i]);
			}
		else $select_ = false;
		
		$wheres = $this->makeWhere($where);
		$query = "SELECT " . ($select_?implode(', ', $select_):$select) . " FROM " . $this->validateTable($tableName) . $wheres[0];

		return $this->query($query, $wheres[1], 'rows');
	}

	public function &get($tableName, $select='*', $where=false){
		if(!$where)
			$where = [];
		$where['LIMIT'] = 1;
		$temp = $this->select($tableName, $select, $where);

		// if empty or false
		if(!$temp){
			$false = false;
			return $false;
		}
		
		// else
		return $temp[0];
	}

	public function &delete($tableName, $where){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		if($where){
			$wheres = $this->makeWhere($where);
			$query = "DELETE FROM " . $this->validateTable($tableName) . $wheres[0];
			return $this->query($query, $wheres[1]);
		}
		else{
			$query = "TRUNCATE TABLE " . $this->validateTable($tableName);
			return $this->query($query, [], 'truncate');
		}
	}

	public function &insert($tableName, $object){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		$objectName = [];
		$objectName_ = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$objectName[] = $this->validateText($columns[$i]);
			$objectName_[] = '?';

			$objectData[] = $object[$columns[$i]];
		}
		$query = "INSERT INTO " . $this->validateTable($tableName) . " (" . implode(', ', $objectName) . ") VALUES (" . implode(', ', $objectName_) . ")";
		
		return $this->query($query, $objectData, 'insertID');
	}

	public function &update($tableName, $object, $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;

		$wheres = $this->makeWhere($where);
		$objectName = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$objectName[] = $this->validateText($columns[$i]).' = ?';
			$objectData[] = $object[$columns[$i]];
		}
		$query = "UPDATE " . $this->validateTable($tableName) . " SET " . implode(', ', $objectName) . $wheres[0];

		return $this->query($query, array_merge($objectData, $wheres[1]));
	}

	public function &drop($tableName){
		if($this->table_prefix !== '') $tableName = $this->table_prefix.'.'.$tableName;
		
		return $this->query("DROP TABLE " . $this->validateTable($tableName), []);
	}
}