<?php
/**
 * Part of the Database Package
 *
 * @copyright  Copyright (C) 2018 Peter Donders
 */

namespace Smoke\Database\Exception;

/**
 * Exception class defining an error executing a statement
 *
 */
class ExecutionFailureException extends \RuntimeException
{
	/**
	 * The SQL statement that was executed.
	 *
	 * @var    string
	 */
	private $query;

	/**
	 * Construct the exception
	 *
	 * @param   string     $query     The SQL statement that was executed.
	 * @param   string     $message   The Exception message to throw. [optional]
	 * @param   integer    $code      The Exception code. [optional]
	 * @param   Exception  $previous  The previous exception used for the exception chaining. [optional]
	 *
	 */
	public function __construct($query, $message = '', $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->query = $query;
	}

	/**
	 * Get the SQL statement that was executed
	 *
	 * @return  string
	 *
	 */
	public function getQuery()
	{
		return $this->query;
	}
}
