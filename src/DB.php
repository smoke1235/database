<?php

namespace Smoke\Database;

class DB
{
	private $type = "mysqli";
	private $prefix = "";


	protected $driver;
	protected static $instance;

	private function __construct($options)
	{

		if (isset($options[ 'database_type' ]))
		{
			$this->type = strtolower($options[ 'database_type' ]);
		}
		if (isset($options[ 'prefix' ]))
		{
			$this->prefix = $options[ 'prefix' ];
		}

		switch ($this->type)
		{
			case 'mysqli':
			case 'mysql':
				$attr = [
					'dbname' => $options[ 'database_name' ],
					'username' => $options[ 'username' ],
					'password' => $options[ 'password' ],
				];

				$attr[ 'host' ] = $options[ 'server' ];
				break;
		}

		$driver = $this->type;
		unset($attr[ 'driver' ]);

		$dsn = "Smoke\\Wrapper\\". ucfirst($driver) . 'Wrapper';
		$fun = "getInstance";
		

		$this->driver = $dsn::$fun($attr);

	}

	public static function getInstance($options) {

		if (!is_array($options))
		{
			return false;
		}

		if( !self::$instance ) {
           self::$instance = new self($options);
        }
        return self::$instance;


		/*if (
			in_array($this->type, ['mariadb', 'mysql', 'pgsql', 'sybase', 'mssql']) &&
			isset($options[ 'charset' ])
		)
		{
			$commands[] = "SET NAMES '" . $options[ 'charset' ] . "'";
		}

		try {
			$this->pdo = new PDO(
				$dsn,
				isset($options[ 'username' ]) ? $options[ 'username' ] : null,
				isset($options[ 'password' ]) ? $options[ 'password' ] : null,
				$this->option
			);
			foreach ($commands as $value)
			{
				$this->pdo->exec($value);
			}
		}
		catch (PDOException $e) {
			throw new PDOException($e->getMessage());
		}*/
    }

	public function query($sql)
	{
		return self::$driver->execute($query);
	}


	/**
	 * Insert a single row in a table
	 *
	 * @param string $table
	 * @param mixed $object
	 * @return bool
	 */
	public function insert($table, $object)
	{
		// Validate table as a valid (not empty) string
		if (!Validator::isString($table))
			return false;

		if (Validator::isObject($object))
		{
			if (method_exists($object, '__beforeDBSave'))
				$object->__beforeDBSave();
			elseif (method_exists($object, '__cleanup'))
				$object->__cleanup();
			$data = get_object_vars($object);
		}
		else
			$data = $object;

		// Validate data as a valid (not empty) array
		if (!Validator::isArray($data, false))
			return false;

		// If table is comma separated (multiple tables), select only the first
		// and encapsulate it in backticks
		$exploded = explode(",", $table);
		$table = "`".reset($exploded)."`";

		// Initialize keys and values
		$keys	= array();
		$values	= array();

		// Loop trough the data and format the keys (columns) and values (fields)
		foreach($data as $key => $value)
		{
			if (!is_array($value) &&  !is_object($value))
		  	{
		  		$value = Strings::escapeForSql($value);

				$keys[]		= "`".str_replace(".", "`.`", $key)."`";
				$values[]	= $value;
		  	}
		}

		// Make comma separated values from both keys and values
		$keys	= implode(", ", $keys);
		$values	= implode(", ", $values);

		// Make the query
		$query = "INSERT INTO $table ($keys) VALUES($values)";

		// Execute the query and return the result
		if ($this->driver->execute($query))
		{
			if (Validator::isObject($object) && method_exists($object, '__afterDBSave'))
				$object->__afterDBSave();

			$insertID = $this->insertID();
			if ($insertID === 0) // No insert ID available (no autoincrement columns)
				return true;
			else
				return $insertID;
		}
		else
			return false;
	}

	/**
	 * Insert multiple rows in a table
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return bool
	 */
	public function insertRows($table, $data)
	{
		// Validate table as a valid (not empty) string
		if (!Validator::isString($table))
			return false;

		if (!Validator::isArray($data, false))
			return false;

		// If table is comma separated (multiple tables), select only the first
		// and encapsulate it in backticks
		$exploded = explode(",", $table);
		$table = "`".reset($exploded)."`";

		$values	= array();
		foreach ($data as $i => $object)
		{
			if (Validator::isObject($object))
			{
				if (method_exists($object, '__beforeDBSave'))
					$object->__beforeDBSave();
				elseif (method_exists($object, '__cleanup'))
					$object->__cleanup();
				$row = get_object_vars($object);
			}
			else
				$row = $object;

			// Validate data as a valid (not empty) array
			if (!Validator::isArray($row, false))
				continue;

			// Initialize keys and values
			$keys	= array();
			$subValues = array();

			// Loop trough the data and format the keys (columns) and values (fields)
			foreach($row as $key => $value)
			{
				if (!is_array($value) && !is_object($value))
			  	{
			  		$value = Strings::escapeForSql($value);

					$keys[]			= "`".str_replace(".", "`.`", $key)."`";
					$subValues[]	= $value;
			  	}
			}

			// Make comma separated values from both keys and values
			$keys		= implode(", ", $keys);
			$values[]	= '('.implode(", ", $subValues).')';
		}

		// Make the query
		$query = "INSERT INTO $table ($keys) VALUES ".implode(", ", $values)."";

		// Execute the query and return the result
		if ($this->driver->execute($query))
		{
			foreach ($data as $i => $object)
			{
				if (Validator::isObject($object) && method_exists($object, '__afterDBSave'))
					$object->__afterDBSave();
			}

			$insertID = $this->insertID();
			if ($insertID === 0) // No insert ID available (no autoincrement columns)
				return true;
			else
				return $insertID;
		}
		else
			return false;
	}

	/**
	 * Replace a single row in a table
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return bool
	 */
	public function replace($table, $object)
	{
		// Validate table as a valid (not empty) string
		if (!Validator::isString($table))
			return false;

		if (Validator::isObject($object))
		{
			if (method_exists($object, '__beforeDBSave'))
				$object->__beforeDBSave();
			elseif (method_exists($object, '__cleanup'))
				$object->__cleanup();
			$data = get_object_vars($object);
		}
		else
			$data = $object;

		// Validate data as a valid (not empty) array
		if (!Validator::isArray($data, false))
			return false;

		// If table is comma separated (multiple tables), select only the first
		// and encapsulate it in backticks
		$explodedTable = explode(",", $table);
		$table = "`".reset($explodedTable)."`";

		// Initialize keys and values
		$values	= array();

		// Loop trough the data and format the keys (columns) and values (fields)
		foreach($data as $key => $value)
		{
			if (is_array($value) || is_object($value))
				continue;

			$value = Strings::escapeForSql($value);
			$values[] = "`".str_replace(".", "`.`", $key)."` = $value";
		}

		// Make comma separated values from both keys and values
		$values	= implode(", ", $values);

		// Make the query
		$query = "REPLACE INTO $table SET $values";

		// Execute the query and return the result
		if ($this->driver->execute($query))
		{
			if (Validator::isObject($object) && method_exists($object, '__afterDBSave'))
				$object->__afterDBSave();

			$insertID = $this->insertID();
			if ($insertID === 0) // No insert ID available (no autoincrement columns)
				return true;
			else
				return $insertID;
		}
		return false;
	}

	/**
	 * Replace multiple rows in a table
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return bool
	 */
	public function replaceRows($table, $data)
	{
		// Validate table as a valid (not empty) string
		if (!Validator::isString($table))
			return false;

		if (!Validator::isArray($data, false))
			return false;

		// If table is comma separated (multiple tables), select only the first
		// and encapsulate it in backticks
		$exploded = explode(",", $table);
		$table = "`".reset($exploded)."`";

		$values	= array();
		foreach ($data as $i => $object)
		{
			if (Validator::isObject($object))
			{
				if (method_exists($object, '__beforeDBSave'))
					$object->__beforeDBSave();
				elseif (method_exists($object, '__cleanup'))
					$object->__cleanup();
				$row = get_object_vars($object);
			}
			else
				$row = $object;

			// Validate data as a valid (not empty) array
			if (!Validator::isArray($row, false))
				continue;

			// Initialize keys and values
			$keys	= array();
			$subValues = array();

			// Loop trough the data and format the keys (columns) and values (fields)
			foreach($row as $key => $value)
			{
				if (is_null($value))
					$value = "NULL";
				elseif (!is_array($value) && !is_object($value))
					$value = Strings::escapeForSql($value);

				if (!is_array($value) && !is_object($value))
			  	{
					$keys[]			= "`".str_replace(".", "`.`", $key)."`";
					$subValues[]	= $value;
			  	}
			}

			// Make comma separated values from both keys and values
			$keys		= implode(", ", $keys);
			$values[]	= '('.implode(", ", $subValues).')';
		}

		// Make the query
		$query = "REPLACE INTO $table($keys) VALUES ".implode(", ", $values)."";

		// Execute the query and return the result
		if ($this->driver->execute($query))
		{
			foreach ($data as $i => $object)
			{
				if (Validator::isObject($object) && method_exists($object, '__afterDBSave'))
					$object->__afterDBSave();
			}

			$insertID = $this->insertID();
			if ($insertID === 0) // No insert ID available (no autoincrement columns)
				return true;
			else
				return $insertID;
		}
		else
			return false;
	}

	/**
	 * Updates rows in one or more tables
	 *
	 * @param mixed $tables Array or comma separated string with tables
	 * @param mixed $data
	 * @param string $where WHERE-clause
	 * @return bool
	 */
	public function update($tables, $object, $where = '')
	{
		if (Validator::isObject($object))
		{
			if (method_exists($object, '__beforeDBSave'))
				$object->__beforeDBSave();
			elseif (method_exists($object, '__cleanup'))
				$object->__cleanup();
			$data = get_object_vars($object);
		}
		else
			$data = $object;

		if (!Validator::isArray($data, false))
			return false;

		if (Validator::isArray($tables))
			$tables = implode(",", $tables);

		if (!Validator::isString($tables))
			return false;

		$tables = str_replace(", ", ",", $tables);
		$tables = str_replace(",", "`,`", $tables);
		$tables = "`".$tables."`";

		$values = array();
		foreach($data as $key => $value)
		{
			if (is_array($value) || is_object($value))
				continue;

			$value = Strings::escapeForSql($value);
			$values[] = "`".str_replace(".", "`.`", $key)."`= $value";
		}

		$values = implode(",", $values);

		$sql = "UPDATE $tables SET ".$values." WHERE ".$where;
		$result = $this->driver->execute($sql);

		if (Validator::isObject($object) && method_exists($object, '__afterDBSave'))
			$object->__afterDBSave();

		return $result;
	}

	/**
	 * Deletes rows from one or more tables
	 *
	 * @param mixed $tables
	 * @param string $where
	 * @return int affected rows
	 */
	public function delete($tables, $where)
	{
		if (!Validator::isString($where))
			return false;

		if (!Validator::isArray($tables))
			$tables = array($tables);

		if (!Validator::isArray($tables, false))
			return false;

		$affected = 0;

		foreach($tables as $table){
			$query = "DELETE FROM `$table` WHERE $where";
			if ($this->driver->execute($query))
				$affected += $this->affectedRows();
		}

		return $affected;
	}

	/**
	 * Select rows from the database
	 * Executes query $query and returns the result as 2 dimentional array
	 *
	 * @param string $query
	 * @param string $rowKeyField What field to use as key in the returned array
	 * @return mixed
	 */
	public function selectRows($query, $rowKeyField = "")
	{
		//$timer = Debug::startTimer('query', 'DB->SelectRows: '.$query);

		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$result = array();
		while ($array = $this->nextRecord())
		{
			if (!Validator::isArray($array, false))
			{
				//Debug::stopTimer($timer);
				return false;
			}

			$keys = array_keys($array);
			foreach($keys as $index => $key)
				$array[$key] = $array[$key];

			if (Validator::isString($rowKeyField) && isset($array[$rowKeyField]))
				$result[$array[$rowKeyField]] = $array;
			else
				$result[] = $array;
		}
		$this->cleanResults();

		//Debug::stopTimer($timer);

		return $result;
	}

	/**
	 * Select a single row from the database
	 * Executes query $query and retuns the first result as array
	 *
	 * @param string $query
	 * @return mixed
	 */
	public function selectRow($query)
	{
		//$timer = Debug::startTimer('query', 'DB->SelectRow: '.$query);


		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$result = array();
		$array = $this->nextRecord();

		if (!Validator::isArray($array, false))
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$keys = array_keys($array);
		foreach($keys as $index => $key)
			$array[$key] = $array[$key];

		$this->cleanResults();

		//Debug::stopTimer($timer);

		return $array;
	}

	/**
	 * Select rows from the database
	 * Executes query $query and returns the result as array of objects
	 *
	 * @param string $query
	 * @param string $class
	 * @param string $rowKeyField What field to use as key in the returned array
	 * @return mixed
	 */
	public function selectObjects($query, $class = "stdClass", $rowKeyField = "")
	{
		//$timer = Debug::startTimer('query', 'DB->SelectObjects: '.$query);

		if (!Validator::isString($class))
			$class = "stdClass";

		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$result = array();
		while ($array = $resultset->fetch())
		{
			if (!Validator::isArray($array, false))
			{
				//Debug::stopTimer($timer);
				return false;
			}

			$object = new $class();

			$keys = array_keys($array);
			foreach($keys as $index => $key)
				$object->$key = $array[$key];

			if (method_exists($object, '__afterDBLoad'))
				$object->__afterDBLoad();

			if (Validator::isString($rowKeyField) && isset($object->$rowKeyField))
				$result[$array[$rowKeyField]] = $object;
			else
				$result[] = $object;
		}

		$resultset->free();

		//Debug::stopTimer($timer);

		return $result;
	}

	/**
	 * Select a single row from the database
	 * Executes query $query and retuns the first result as object
	 *
	 * @param string $query
	 * @param string $class
	 * @return mixed
	 */
	public function selectObject($query, $class = "stdClass")
	{
		//$timer = Debug::startTimer('query', 'DB->SelectObject: '.$query);

		$object = new $class();

		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return $object;
		}

		$result = array();
		$array = $this->nextRecord();

		if (!Validator::isArray($array, false))
		{
			//Debug::stopTimer($timer);
			return $object;
		}

		$keys = array_keys($array);
		foreach($keys as $index => $key)
			$object->$key = $array[$key];

		if (method_exists($object, '__afterDBLoad'))
			$object->__afterDBLoad();

		$this->cleanResults();

		//Debug::stopTimer($timer);

		return $object;
	}

	/**
	 * Select a single value from the database
	 * Execute query $query and return the value of $key (or the first column)
	 * Returns false when $key or no results were found.
	 *
	 * @param string $query
	 * @param string $key Name of the field that will be returned. When false, the first field is returned.
	 * @return string|false
	 */
	public function selectValue($query, $key = false)
	{
		//$timer = Debug::startTimer('query', 'DB->selectValue: '.$query);

		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$result = array();
		$array = $resultset->fetch();

		if (!Validator::isArray($array, false))
		{
			//Debug::stopTimer($timer);
			return false;
		}

		if ($key && isset($array[$key]))
		{
			$resultset->free();

			//Debug::stopTimer($timer);
			return $array[$key];
		}
		elseif ($key)
		{
			//Debug::stopTimer($timer);
			return false;
		}
		else
		{
			$resultset->free();
			//Debug::stopTimer($timer);
			return reset($array);
		}
	}

	/**
	 * Select an array with values from the database
	 * Execute query $query and return the values of $key (or the first column) in an array
	 * Whereas selectRows creates a two-dimentional array with values
	 * Returns false when $key or no results were found.
	 *
	 * @since 1.1.0
	 * @param string $query
	 * @param string $key Name of the fields that will be returned. When false, the first column is returned.
	 * @param string $rowKeyField Name of the field that will be used as key in the returned array. When false, the array is not associative.
	 * @return mixed|false
	 */
	public function selectValues($query, $key = false, $rowKeyField = false)
	{
		//$timer = Debug::startTimer('query', 'DB->SelectValues: '.$query);

		$resultset = $this->driver->execute($query);
		if ($resultset === false)
		{
			//Debug::stopTimer($timer);
			return false;
		}

		$result = array();
		while($array = $resultset->fetch())
		{
			if (!Validator::isArray($array, false))
				continue;

			if ($key && isset($array[$key]))
			{
				if ($rowKeyField && isset($array[$rowKeyField]))
					$result[$array[$rowKeyField]] = $array[$key];
				else
					$result[] = $array[$key];
			}
			elseif ($key)
				continue;
			else
			{
				if ($rowKeyField && isset($array[$rowKeyField]))
					$result[$array[$rowKeyField]] = reset($array);
				else
					$result[] = reset($array);
			}
		}
		$resultset->free();

		//Debug::stopTimer($timer);

		return $result;
	}
	
	/**
	 * Get the current query object or a new DatabaseQuery object.
	 *
	 * @param   boolean  $new  False to return the current query object, True to return a new DatabaseQuery object.
	 *
	 * @return  DatabaseQuery  The current query object or a new object extending the DatabaseQuery class.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
		    
			// Derive the class name from the driver.
			$class = 'Smoke\\Wrapper\\' . ucfirst($this->type) . '\\' . ucfirst($this->type) . 'Query';
			// Make sure we have a query class for this driver.
			if (!class_exists($class))
			{
				// If it doesn't exist we are at an impasse so throw an exception.
				throw new Exception\UnsupportedAdapterException('Database Query Class not found.');
			}
			return new $class($this);
		}
		return $this->sql;
	}


}
