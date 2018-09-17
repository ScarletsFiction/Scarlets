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

class SQL {
	public $SQLConnection;
	public $transactionCounter = 0;
	public $debug = false;
	public $table_prefix = '';

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
		if(!isset($options['table_prefix'])) $options['table_prefix'] = '';
		if(!isset($options['charset'])) $options['charset'] = 'utf8';
		if(!isset($options['collation'])) $options['collation'] = 'utf8_unicode_ci';
		if(!isset($options['debug'])) $options['debug'] = false;
		if(!isset($options['database'])) throw new \Exception("Database name was not specified");

		$this->debug = &$options['debug'];
		$this->table_prefix = &$options['table_prefix'];

		// Try to connect
		try{
			$this->SQLConnection = new \PDO("$options[driver]:dbname=$options[database];host=$options[host];port=$options[port]", $options['user'], $options['password']);
		}
		catch(\PDOException $e) {
			throw new \PDOException($e->getMessage());
		}
	}

	// SQLQuery
	public function query($query, $arrayData, $from){
		if($this->debug)
			$this->lastQuery = [$query, $arrayData];

		$statement = $this->SQLConnection->prepare($query);
		$result = $statement->execute($arrayData);

		$error = $statement->errorInfo();
		if(!empty($error[2]))
			throw new \Exception($error[2]);

		if($from === 'select')
			$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
		if($from === 'insert')
			$result = $this->SQLConnection->lastInsertId();

		return $result;
	}

    public function beginTransaction()
    {
        if(!$this->transactionCounter++)
            return parent::beginTransaction();
        $this->exec('SAVEPOINT trans'.$this->transactionCounter);
        return $this->transactionCounter >= 0;
    }

    public function commit()
    {
        if(!--$this->transactionCounter)
            return parent::commit();
        return $this->transactionCounter >= 0;
    }

    public function rollback()
    {
        if(--$this->transactionCounter){
            $this->exec('ROLLBACK TO trans'.$this->transactionCounter + 1);
            return true;
        }
        return parent::rollback();
    }

	// The code below could be similar with Javascript version
	// But the PHP version doesn't include preprocessData
	private function validateText($text){
		return '`'.preg_replace('/[^a-zA-Z0-9_\.]+/m', '', $text).'`';
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
	public function makeWhere($object, $comparator=false, $children=false){
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
						throw new \Exception('SQL where: value of ' . $this->validateText($matches[1]) . ' is non-numeric and can\'t be accepted');
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
							throw new \Exception('SQL where: value of' . $this->validateText($matches[1]) . ' with type ' . $type . ' can\'t be accepted');
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
						if(strpos($value, '%') === false) $value[$a] = '%'.$value[$a].'%';
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
						throw new \Exception('SQL where: value ' . $this->validateText($matches[1]) . ' with type ' . $type . ' can\'t be accepted');
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
			if(!is_nan($object['LIMIT'])){
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

	public function createTable($tableName, $columns)
	{
		if($this->table_prefix !== '') $tableName = $this->table_prefix;

		$columns_ = array_keys($columns);
		for($i = 0; $i < count($columns_); $i++){
			if(gettype($columns[$columns_[$i]]) === 'array'){
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper($columns[$columns_[$i]][0]).' '.$this->validateText($columns[$columns_[$i]][1]);
			}
			else
				$columns_[$i] = $this->validateText($columns_[$i]).' '.strtoupper(strval($columns[$columns_[$i]]));
		}
		$query = 'CREATE TABLE IF NOT EXISTS '.$this->validateText($tableName).' ('.implode(', ', $columns_).')';

		return $this->query($query, [], 'create');
	}

	public function select($tableName, $select='*', $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix;

		$select_ = $select;
		if($select!=='*')
			for($i = 0; $i < count($select_); $i++){
				$select_[$i] = $this->validateText($select_[$i]);
			}
		else $select_ = false;
		
		$wheres = $this->makeWhere($where);
		$query = "SELECT " . ($select_?implode(', ', $select_):$select) . " FROM " . $this->validateText($tableName) . $wheres[0];
		
		return $this->query($query, $wheres[1], 'select');
	}

	public function delete($tableName, $where){
		if($this->table_prefix !== '') $tableName = $this->table_prefix;

		if($where){
			$wheres = $this->makeWhere($where);
			$query = "DELETE FROM " . $this->validateText($tableName) . $wheres[0];
			return $this->query($query, $wheres[1], 'delete');
		}
		else{
			$query = "TRUNCATE TABLE " . $this->validateText($tableName);
			return $this->query($query, [], 'truncate');
		}
	}

	public function insert($tableName, $object){
		if($this->table_prefix !== '') $tableName = $this->table_prefix;

		$objectName = [];
		$objectName_ = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$objectName[] = $this->validateText($columns[$i]);
			$objectName_[] = '?';

			$objectData[] = $object[$columns[$i]];
		}
		$query = "INSERT INTO " . $this->validateText($tableName) . " (" . implode(', ', $objectName) . ") VALUES (" . implode(', ', $objectName_) . ")";
		
		return $this->query($query, $objectData, 'insert');
	}

	public function update($tableName, $object, $where=false){
		if($this->table_prefix !== '') $tableName = $this->table_prefix;

		$wheres = $this->makeWhere($where);
		$objectName = [];
		$objectData = [];
		$columns = array_keys($object);
		for($i = 0; $i < count($columns); $i++){
			$objectName[] = $this->validateText($columns[$i]).' = ?';
			$objectData[] = $object[$columns[$i]];
		}
		$query = "UPDATE " . $this->validateText($tableName) . " SET " . implode(', ', $objectName) . $wheres[0];

		return $this->query($query, array_merge($objectData, $wheres[1]), 'update');
	}

	public function drop($tableName){
		if($this->table_prefix !== '') $tableName = $this->table_prefix;
		
		return $this->query("DROP TABLE " . $this->validateText($tableName), [], 'drop');
	}
}