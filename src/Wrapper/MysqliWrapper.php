<?php

namespace Smoke\Wrapper;

class MysqliWrapper extends \MySQLi
{
	protected static $instance;
	protected $debugNextQuery = false;
	protected static $queryCount	= 0;
	protected static $queryTime		= 0;

	public function __construct($options) {

		print_r($options);

        // turn of error reporting
        //mysqli_report(MYSQLI_REPORT_OFF);

        // connect to database
        parent::__construct($options['host'], $options['username'], $options['password'], $options['dbname']);

        // check if a connection established
        if( mysqli_connect_errno() ) {
            throw new exception(mysqli_connect_error(), mysqli_connect_errno());
        }
    }

	public static function getInstance($options) {
        if( !self::$instance ) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }


	public function execute($query)
	{
		//$query = str_replace(Core::$httpPath, '%%HTTP_PATH%%', $query);

		$startTime = array_sum(explode(" ",microtime()));

		$this->lastQuery = $query;
		$this->real_query($query);

		$endTime = array_sum(explode(" ",microtime()));
		$time = $endTime - $startTime;

		$this->row			= 0;
		$this->errorID		= mysqli_errno($this);
		$this->errorMessage	= mysqli_error($this);

		if ($this->debugNextQuery)
		{
			$this->debugQuery();
			$this->debugNextQuery = false;
		}

		//if (!$this->queryID)
		//{
		//	if (LOGGING)
		//	{
		//		$logLine = "Invalid SQL Query: Error $this->errorID ($this->errorMessage) while executing \"$query\"";
		//		Debug::log('query_errors', $logLine, true, 1, 2);
		//	}
		//	$this->halt("Invalid SQL: \"".$query."\"");
		//	return false;
		//}

		/*if (DEBUG || LOG_QUERIES)
		{
			self::$queryCount++;
			self::$queryTime += $time;

			if (LOG_QUERIES){
				$logLine = '['.number_format(($time*1000), 2).' ms] '.$query;
				Debug::log('queries', $logLine, true, 1, 2);
			}
		}*/

		return new MysqliResultWrapper($this);
	}
}
