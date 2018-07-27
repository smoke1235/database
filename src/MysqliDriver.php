<?php
/**
 * Part of the Database Package
 *
 */

namespace Donders\Database\Mysqli;

use Donders\Database\DatabaseDriver;
//use Joomla\Database\DatabaseEvents;
//use Joomla\Database\Event\ConnectionEvent;
//use Joomla\Database\Exception\ConnectionFailureException;
//use Joomla\Database\Exception\ExecutionFailureException;
//use Joomla\Database\Exception\PrepareStatementFailureException;
//use Joomla\Database\Exception\UnsupportedAdapterException;
use Donders\Database\StatementInterface;
//use Joomla\Database\UTF8MB4SupportInterface;

/**
 * MySQLi Database Driver
 *
 * @link   https://secure.php.net/manual/en/book.mysqli.php
 */
class MysqliDriver extends DatabaseDriver
{
	/**
	 * The database connection resource.
	 *
	 * @var    \mysqli
	 */
	protected $connection;

	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 */
	public $name = 'mysqli';

	/**
	 * The character(s) used to quote SQL statement names such as table names or field names, etc.
	 *
	 * If a single character string the same character is used for both sides of the quoted name, else the first character will be used for the
	 * opening quote and the second for the closing quote.
	 *
	 * @var    string
	 */
	protected $nameQuote = '`';

	/**
	 * The null or zero representation of a timestamp for the database driver.
	 *
	 * @var    string
	 */
	protected $nullDate = '0000-00-00 00:00:00';

	/**
	 * The minimum supported database version.
	 *
	 * @var    string
	 */
	protected static $dbMinimum = '5.5.3';

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 *
	 */
	public function __construct(array $options)
	{
		/**
		 * sql_mode to MySql 5.7.8+ default strict mode minus ONLY_FULL_GROUP_BY because it's inconvenient for some.
		 *
		 * @link https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-8.html#mysqld-5-7-8-sql-mode
		 */
		$sqlModes = [
			'STRICT_TRANS_TABLES',
			'ERROR_FOR_DIVISION_BY_ZERO',
			'NO_AUTO_CREATE_USER',
			'NO_ENGINE_SUBSTITUTION',
		];

		// Get some basic values from the options.
		$options['host']     = isset($options['host']) ? $options['host'] : 'localhost';
		$options['user']     = isset($options['user']) ? $options['user'] : 'root';
		$options['password'] = isset($options['password']) ? $options['password'] : '';
		$options['database'] = isset($options['database']) ? $options['database'] : '';
		$options['select']   = isset($options['select']) ? (bool) $options['select'] : true;
		$options['port']     = isset($options['port']) ? (int) $options['port'] : null;
		$options['socket']   = isset($options['socket']) ? $options['socket'] : null;
		$options['sqlModes'] = isset($options['sqlModes']) ? (array) $options['sqlModes'] : $sqlModes;

		// Finalize initialisation.
		parent::__construct($options);
	}

	/**
	 * Connects to the database if needed.
	 *
	 * @return  void  Returns void if the database connected successfully.
	 *
	 * @throws  \RuntimeException
	 */
	public function connect()
	{
		if ($this->connection)
		{
			return;
		}

		// Make sure the MySQLi extension for PHP is installed and enabled.
		if (!static::isSupported())
		{
			throw new \RuntimeException('The MySQLi extension is not available');
		}

		/*
		 * Unlike mysql_connect(), mysqli_connect() takes the port and socket as separate arguments. Therefore, we
		 * have to extract them from the host string.
		 */
		$port = isset($this->options['port']) ? $this->options['port'] : 3306;

		if (preg_match(
			'/^(?P<host>((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))(:(?P<port>.+))?$/',
			$this->options['host'],
			$matches
		))
		{
			// It's an IPv4 address with or without port
			$this->options['host'] = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		elseif (preg_match('/^(?P<host>\[.*\])(:(?P<port>.+))?$/', $this->options['host'], $matches))
		{
			// We assume square-bracketed IPv6 address with or without port, e.g. [fe80:102::2%eth1]:3306
			$this->options['host'] = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		elseif (preg_match('/^(?P<host>(\w+:\/{2,3})?[a-z0-9\.\-]+)(:(?P<port>[^:]+))?$/i', $this->options['host'], $matches))
		{
			// Named host (e.g example.com or localhost) with or without port
			$this->options['host'] = $matches['host'];

			if (!empty($matches['port']))
			{
				$port = $matches['port'];
			}
		}
		elseif (preg_match('/^:(?P<port>[^:]+)$/', $this->options['host'], $matches))
		{
			// Empty host, just port, e.g. ':3306'
			$this->options['host'] = 'localhost';
			$port = $matches['port'];
		}

		// ... else we assume normal (naked) IPv6 address, so host and port stay as they are or default

		// Get the port number or socket name
		if (is_numeric($port))
		{
			$this->options['port'] = (int) $port;
		}
		else
		{
			$this->options['socket'] = $port;
		}

		// Make sure the MySQLi extension for PHP is installed and enabled.
		if (!static::isSupported())
		{
			throw new UnsupportedAdapterException('The MySQLi extension is not available');
		}

		$this->connection = mysqli_init();

		// Attempt to connect to the server, use error suppression to silence warnings and allow us to throw an Exception separately.
		$connected = @$this->connection->real_connect(
			$this->options['host'], $this->options['user'], $this->options['password'], null, $this->options['port'], $this->options['socket']
		);

		if (!$connected)
		{
			throw new ConnectionFailureException(
				'Could not connect to MySQL: ' . $this->connection->connect_error,
				$this->connection->connect_errno
			);
		}

		// If needed, set the sql modes.
		if ($this->options['sqlModes'] !== [])
		{
			$this->connection->query('SET @@SESSION.sql_mode = \'' . implode(',', $this->options['sqlModes']) . '\';');
		}

		// And read the real sql mode to mitigate changes in mysql > 5.7.+
		$this->options['sqlModes'] = explode(',', $this->setQuery('SELECT @@SESSION.sql_mode;')->loadResult());

		// If auto-select is enabled select the given database.
		if ($this->options['select'] && !empty($this->options['database']))
		{
			$this->select($this->options['database']);
		}

	}

	/**
	 * Disconnects the database.
	 *
	 * @return  void
	 *
	 */
	public function disconnect()
	{
		// Close the connection.
		if (is_callable($this->connection, 'close'))
		{
			$this->connection->close();
		}

		parent::disconnect();
	}

	/**
	 * Method to escape a string for usage in an SQL statement.
	 *
	 * @param   string   $text   The string to be escaped.
	 * @param   boolean  $extra  Optional parameter to provide extra escaping.
	 *
	 * @return  string  The escaped string.
	 *
	 */
	public function escape($text, $extra = false)
	{
		if (is_int($text))
		{
			return $text;
		}

		if (is_float($text))
		{
			// Force the dot as a decimal point.
			return str_replace(',', '.', $text);
		}

		$this->connect();

		$result = $this->connection->real_escape_string($text);

		if ($extra)
		{
			$result = addcslashes($result, '%_');
		}

		return $result;
	}

	/**
	 * Test to see if the MySQLi connector is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 */
	public static function isSupported()
	{
		return extension_loaded('mysqli');
	}

	/**
	 * Determines if the connection to the server is active.
	 *
	 * @return  boolean  True if connected to the database engine.
	 *
	 */
	public function connected()
	{
		if (\is_object($this->connection))
		{
			return $this->connection->ping();
		}

		return false;
	}

	/**
	 * Get the version of the database connector.
	 *
	 * @return  string  The database connector version.
	 *
	 */
	public function getVersion()
	{
		$this->connect();

		return $this->connection->server_info;
	}
	
	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  mixed  The value of the auto-increment field from the last inserted row.
	 *                 If the value is greater than maximal int value, it will return a string.
	 *
	 */
	public function insertid()
	{
		$this->connect();
		return $this->connection->insert_id;
	}

	
	/**
	 * Locks a table in the database.
	 *
	 * @param   string  $table  The name of the table to unlock.
	 *
	 * @return  $this
	 *
	 * @throws  \RuntimeException
	 */
	public function lockTable($table)
	{
		$this->executeUnpreparedQuery($this->replacePrefix('LOCK TABLES ' . $this->quoteName($table) . ' WRITE'));
		return $this;
	}

	/**
	 * Select a database for use.
	 *
	 * @param   string  $database  The name of the database to select for use.
	 *
	 * @return  boolean  True if the database was successfully selected.
	 *
	 * @throws  \RuntimeException
	 */
	public function select($database)
	{
		$this->connect();
		if (!$database)
		{
			return false;
		}
		if (!$this->connection->select_db($database))
		{
			throw new ConnectionFailureException('Could not connect to database.');
		}
		return true;
	}
	
	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 *
	 */
	protected function freeResult($cursor = null)
	{
		$this->executed = false;
		if ($this->statement)
		{
			$this->statement->closeCursor();
			$this->statement = null;
		}
	}

	
	/**
	 * Unlocks tables in the database.
	 *
	 * @return  $this
	 *
	 * @throws  \RuntimeException
	 */
	public function unlockTables()
	{
		$this->executeUnpreparedQuery('UNLOCK TABLES');
		return $this;
	}
	
	/**
	 * Prepares a SQL statement for execution
	 *
	 * @param   string  $query  The SQL query to be prepared.
	 *
	 * @return  StatementInterface
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  PrepareStatementFailureException
	 */
	protected function prepareStatement(string $query): StatementInterface
	{
		return new MysqliStatement($this->connection, $query);
	}
}