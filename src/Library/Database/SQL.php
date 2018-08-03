<?php
	namespace Scarlets\Library\Database;
	class SQL {
		public $SQLConnection;

		public function __construct($databaseName, $options){
			// Default options
			if(!$options['host']) $options['host'] = 'localhost';
			if(!$options['user']) $options['user'] = 'root';
			if($options['username']) $options['user'] = $options['username'];
			if(!$options['password']) $options['password'] = '';
			if(!$options['driver']) $options['driver'] = '';
			if(!$options['prefix']) $options['password'] = '';
			if(!$options['charset']) $options['charset'] = '';
			if(!$options['collation']) $options['collation'] = '';

			// Try to connect
			try{
				$this->SQLConnection = new PDO($options['driver'].':host='.$options['host'].';dbname='.$databaseName, $options['user'], $options['password']);
			}
			catch(PDOException $e) {
				throw new PDOException($e->getMessage());
			}
		}

		private function validateText($text){
			return preg_replace('/[^a-zA-Z0-9_\.]+/m', '', $text);
		}

		public function query($query, $arrayData){
			$statement = $this->SQLConnection->prepare($query);
			$statement->execute($arrayData);
			$result = $statement->fetchAll(PDO::FETCH_ASSOC);
			$error = $statement->errorInfo();
			if(!empty($error[2]))
				throw new Exception($error[2]);

			return $result;
		}

		public function createTable($table, $columns)
		{
			$columns_ = array_keys($columns);
			for($i = 0; $i < count($columns_); $i++){
				if(gettype($columns[$columns_[$i]]) == 'array'){
					$extended = $columns[$columns_[$i]];
					$columns_[$i] = '`'.$this->validateText($columns_[$i]).'`';
					for ($a=0; $a < count($extended); $a++) { 
						$columns_[$i] .= ' '.$this->validateText(strtoupper($extended[$a]));
					}
				}
				else
					$columns_[$i] = '`'.$this->validateText($columns_[$i]).'` '.strtoupper(strval($columns[$columns_[$i]]));
			}
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->validateText($table).'` ('.implode(', ', $columns_).')';

			return $this->query($query, []);
		}

		public function select($table, $column='*', $where=false){
			$table_ = $this->validateText($table);

			// Arrange column data
			if($column=='*')
				$column_ = '*';
			elseif(!is_array($column))
				throw new Exception('SQL select: column is not an array');
			else{
				$column_ = [];
				for ($i=0; $i < count($column); $i++)
					$column_[] = $this->validateText($column[$i]); // Remove invalid character

				$column_ = "`".implode('`,`', $column_)."`";
			}
			
			$wheres = $this->makeWhere($where);
			$query = "SELECT $column_ FROM `$table_` ".$wheres[0];
			
			return $this->query($query, $wheres[1]);
		}

		public function insert($table, $field){
			$table_ = $this->validateText($table);
			
			$fields = $this->makeField($field);
			$values = [];
			foreach($fields[0] as $column){
				$values[] = '?';
			}
			$fields[0] = "(`".(implode('`,`', $fields[0]))."`) VALUES (".(implode(',', $values)).")";
			$query = "INSERT INTO `$table_` ".$fields[0];
			
			return $this->query($query, $fields[1]);
		}

		public function update($table, $field, $where=false){
			$table_ = $this->validateText($table);
			
			$fields = $this->makeField($field);
			$wheres = $this->makeWhere($where);
			$fields_ = [];
			foreach($fields[0] as $column){
				$fields_[] = '`'.$this->validateText($column).'` = ?';
			}
			$fields[1] = array_merge($fields[1], $wheres[1]);
			$fields_ = implode(', ', $fields_);
			$query = "UPDATE `$table_` SET ".$fields_." ".$wheres[0];
			
			return $this->query($query, $fields[1]);
		}

		public function delete($table, $where){
			$table_ = $this->validateText($table);
			
			$wheres = $this->makeWhere($where);
			$query = "DELETE FROM `$table_` ".$wheres[0];
			
			return $this->query($query, $wheres[1]);
		}

		public function drop($table){
			$table_ = $this->validateText($table);
			return $this->query("DROP TABLE ".$table_, []);
		}
		
		// Return: [FieldsName, Values]
		public function makeField($field){
			if(!is_array($field))
				throw new Exception('SQL makeField: is not an array');
			
			$field_ = array_keys($field);
			foreach($field_ as &$keys){
				$keys = $this->validateText($keys);
			}
			
			$data = [];
			foreach($field as $keys => $value){
				$data[] = $value;
			}
			
			return [$field_, $data];
		}
		
		/* namaKolom[operator]
			~ [Like]
			! [Not]
			tanpa operator (Equal)
			>= atau > (More than)
			<= atau < (Lower than)
		*/
		// {('AND', 'OR'), 'ORDER':{columnName:'ASC', 'DESC'}, 'LIMIT':[startIndex, rowsLimit]}

		// ex: ["AND"=>["id"=>12, "OR"=>["name"=>"myself", "name"=>"himself"]], "LIMIT"=>1]
			// Select one where (id == 12 && (name == "myself" || name == "himself"))
		public function makeWhere($object, $comparator=false, $relational=false){
			if(!$object) return ['', []];
			$wheres = [];

			$objectData = [];
			$columns = array_keys($object);
			$defaultConditional = ' AND ';
			$relations = false;
			$onlySpecial = false;

			for ($i = 0; $i < count($columns); $i++) {
				if($columns[$i]=='ORDER'||$columns[$i]=='LIMIT'){
	                $onlySpecial = true;
	                continue;
	            }
				$test = explode('AND', $columns[$i]);
				$haveRelation = false;
				if(count($test) == 2 && $test[0] == ''){
					$defaultConditional = ' AND ';
					$haveRelation = true;
				}
				else{
					$test = explode('OR', $columns[$i]);
					if(count($test) == 2 && $test[0] == ''){
						$defaultConditional = ' OR ';
						$haveRelation = true;
					}
				}
				if($haveRelation){
					$childs = $this->makeWhere($object[$columns[$i]], $defaultConditional, true);
					$wheres[] = '('.$childs[0].')';
					$objectData = array_merge($objectData, $childs[1]);
					$haveRelation = false;
					$relations = true;
					$onlySpecial = false;
				}
			}

			if(!$relations&&!$onlySpecial)
				for($i = 0; $i < count($columns); $i++){
					$value = $object[$columns[$i]];

					preg_match('/([a-zA-Z0-9_\.]+)(\[(\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\])?/i', $columns[$i], $matches);
					if(isset($matches[3])){
						if(in_array($matches[3], ['>', '>=', '<', '<=']))
						{
							if(!is_nan($value))
							{
								$wheres[] = $key.' '.$matches[3].' ?';
								$objectData[] = $value;
								continue;
							}
							else
								throw new Exception('SQL where: value of '.$columns[$i].' is non-numeric and can\'t be accepted');
						}
						else if($matches[3] == '!')
						{
							$type = gettype($value);
							if(!$type)
								$wheres[] = $columns[$i].' IS NOT NULL';
							else
								switch($type)
								{
									case 'array':
										$temp = [];
										for ($i = 0; $i < count($value); $i++) {
											$temp[] = '?';
										}
										$wheres[] = $columns[$i].' NOT IN ('. implode(', ', $temp).')';
										$objectData = array_merge($objectData, $value);
									break;
									case 'integer':
									case 'double':
									case 'boolean':
									case 'string':
										$wheres[] = $columns[$i].' != ?';
										$objectData[] = $value;
									break;
									default:
										throw new Exception('SQL where: value '.$columns[$i].' with type '.$type.' can\'t be accepted');
									break;
								}
						}
						else if ($matches[3] == '~' || $matches[3] == '!~')
						{
							if(gettype($value) != 'array'){
								$value = [$value];
							}
							$likes = [];
							for ($i = 0; $i < count($value); $i++) {
								$likes[] = $columns[$i].($matches[3] === '!~' ? ' NOT' : '').' LIKE ?';
								if(strpos($value, '%') === false) $value[$i] = '%'.$value[$i].'%';
								$objectData[] = $value[$i];
							}

	                        $wheres[] = '('.implode(' OR ', $likes).')';
						}
					} else {
						$type = gettype($value);
						if(!$type)
							$wheres[] = $columns[$i].' IS NULL';
						else
							switch($type){
								case 'array':
									$temp = [];
									for ($i = 0; $i < count($value); $i++) {
										$temp[] = '?';
									}
									$wheres[] = $columns[$i].' IN ('.implode(', ', $temp).')';
									$objectData = array_merge($objectData, $value);
								break;
								case 'integer':
								case 'double':
								case 'boolean':
								case 'string':
									$wheres[] = $columns[$i].' = ?';
									$objectData[] = $value;
								break;
								default:
									throw new Exception('SQL where: value '.$columns[$i].' with type '.$type.' can\'t be accepted');
								break;
							}
					}
				}

			$options = '';
			if(isset($object['ORDER'])){
				$columns = array_keys($object['ORDER']);
				$stack = [];
				for($i = 0; $i < count($columns); $i++){
					$order = strtoupper($object['ORDER'][$columns[$i]]);
					if($order != 'ASC' && $order != 'DESC') continue;
					$stack[] = $this->validateText($columns[$i]).' '.$order;
				}
				$options = $options.' ORDER BY '.implode(', ', $stack);
			}
			if(isset($object['LIMIT'])){
				if(!is_nan($object['LIMIT'][0]) && !is_nan($object['LIMIT'][1])){
					$options = $options.' LIMIT '.$object['LIMIT'][1].' OFFSET '.$object['LIMIT'][0];
				}
			}
			
			$where_ = '';
			if(count($wheres) != 0){
				if(!$relational)
					$where_ = $where_." WHERE ";
				$where_ = $where_.implode($comparator ? $comparator : $defaultConditional, $wheres);
			}
			
			return [$where_.$options, $objectData];
		}
	}