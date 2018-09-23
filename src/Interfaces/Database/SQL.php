<?php
namespace Scarlets\Interfaces\Database;

interface SQL {
	public $connection;
	public $debug;
	public $type;
    public $lastQuery;
    
    /**
     * Change current database
     * 
     * This may work for MySQL only and will 
	 * back to original state after the query
	 * was executed

     * @param array $options 
     * @return void
     */
    public function change($options);

    /**
     * Database query
     *
     * @param string $query
     * @param array $arrayData
     * @param string $from 'insert' 
     * @return int|array
     */
    public function &query($query, $arrayData, $from);
    
    /**
     * Run database transaction on a function scope.
     * You can return true value or throw error to rollback.
     *
     * @param callback $func
     * @return void
     */
    public function transaction($func);

    /**
     * Register a callback when the table was missing
     * 
     * @param string $table
     * @param callback $func
     * @return void
     */
    public function onTableMissing($table, $func);

    /**
     * Create a table on the database
     * 
     * @param string $tableName
     * @param array $columns
     * @return object PDO resource
     */
    public function createTable($tableName, $columns);

    /**
     * Count available rows on the database table
     * 
     * @param string $tableName
     * @param array $where
     * @return int
     */
    public function count($tableName, $where = false);

    /**
     * Select rows data from database table
     * 
     * @param string $tableName
     * @param array|string $select
     * @param array $where
     * @return array
     */
    public function select($tableName, $select='*', $where = false);

    /**
     * Get single row data from database table
     * 
     * @param string $tableName
     * @param array|string $select
     * @param array $where
     * @return array
     */
    public function get($tableName, $select='*', $where = false);

    /**
     * Delete rows data from database table
     * Can be used for truncating if $where is false
     * 
     * @param string $tableName
     * @param array|string $select
     * @param array $where
     * @return object PDO resource
     */
    public function delete($tableName, $where = false);

    /**
     * Insert single row to the database
     * 
     * @param string $tableName
     * @param array|string $select
     * @param array $where
     * @return int Last insert ID
     */
    public function insert($tableName, $where = false);

    /**
     * Update rows data on the database
     * 
     * @param string $tableName
     * @param array $object
     * @param array $where
     * @return object PDO resource
     */
    public function update($tableName, $object, $where = false);

    /**
     * Drop table from the database
     * 
     * @param string $tableName
     * @param array $object
     * @param array $where
     * @return object PDO resource
     */
    public function drop($tableName);
}