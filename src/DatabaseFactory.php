<?php

/**
 * Part of the Database Package
 *
 */


namespace Donders\Database;


class DatabaseFactory
{
	/**
	 * Method to return a DatabaseDriver instance based on the given options.
	 *
	 * There are three global options and then the rest are specific to the database driver. 
	 * The 'database' option determines which database is to be used for the connection. 
	 * The 'select' option determines whether the connector should automatically select the chosen database.
	 *
	 * @param   string  $name     Name of the database driver you'd like to instantiate
	 * @param   array   $options  Parameters to be passed to the database driver.
	 *
	 * @return  DatabaseDriver
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public static function getDriver($name = 'mysqli', array $options = [])
	{
		// Sanitize the database connector options.
		$options['driver']   = preg_replace('/[^A-Z0-9_\.-]/i', '', $name);
		$options['database'] = isset($options['database']) ? $options['database'] : null;
		$options['select']   = isset($options['select']) ? $options['select'] : true;
		
		// Derive the class name from the driver.
		$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($options['driver'])) . '\\' . ucfirst(strtolower($options['driver'])) . 'Driver';
		
		// If the class still doesn't exist we have nothing left to do but throw an exception.  We did our best.
		if (!class_exists($class))
		{
			throw new Exception\UnsupportedAdapterException(sprintf('Unable to load Database Driver: %s', $options['driver']));
		}
		
		// Create our new DatabaseDriver connector based on the options given.
		try
		{
			return new $class($options);
		}
		catch (\RuntimeException $e)
		{
			throw new Exception\ConnectionFailureException(sprintf('Unable to connect to the Database: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}
	
	/**
	 * Get the current query object or a new Query object.
	 *
	 * @param   string             $name  Name of the driver you want an query object for.
	 * @param   DatabaseInterface  $db    Optional Driver instance
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0
	 * @throws  Exception\UnsupportedAdapterException
	 */
	public function getQuery($name, DatabaseInterface $db = null)
	{
		// Derive the class name from the driver.
		$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($name)) . '\\' . ucfirst(strtolower($name)) . 'Query';
		// Make sure we have a query class for this driver.
		if (!class_exists($class))
		{
			// If it doesn't exist we are at an impasse so throw an exception.
			throw new Exception\UnsupportedAdapterException('Database Query class not found');
		}
		return new $class($db);
	}

}