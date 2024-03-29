<?php

namespace Modufolio\Database;

use Closure;
use Exception;
use Modufolio\Exception\InvalidArgumentException;
use Modufolio\Toolkit\A;
use Modufolio\Toolkit\Str;
use PDO;
use PDOStatement;
use Throwable;

/**
 * A simple database class
 *
 * @package   Kirby Database
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier GmbH
 * @license   https://opensource.org/licenses/MIT
 */
class Database
{
    /**
     * The number of affected rows for the last query
     *
     * @var int|null
     */
    protected $affected;

    /**
     * Whitelist for column names
     *
     * @var array
     */
    protected $columnWhitelist = [];

    /**
     * The established connection
     *
     * @var PDO|null
     */
    protected $connection;

    /**
     * A global array of started connections
     *
     * @var array
     */
    public static $connections = [];

    /**
     * Database name
     *
     * @var string
     */
    protected $database;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * Set to true to throw exceptions on failed queries
     *
     * @var bool
     */
    protected $fail = false;

    /**
     * The connection id
     *
     * @var string
     */
    protected $id;

    /**
     * The last error
     *
     * @var Exception|null
     */
    protected $lastError;

    /**
     * The last insert id
     *
     * @var int|null
     */
    protected $lastId;

    /**
     * The last query
     *
     * @var string
     */
    protected $lastQuery;

    /**
     * The last result set
     *
     * @var mixed
     */
    protected $lastResult;

    /**
     * Optional prefix for table names
     *
     * @var string
     */
    protected $prefix;

    /**
     * The PDO query statement
     *
     * @var PDOStatement|null
     */
    protected $statement;

    /**
     * List of existing tables in the database
     *
     * @var array|null
     */
    protected $tables;

    /**
     * An array with all queries which are being made
     *
     * @var array
     */
    protected $trace = [];

    /**
     * The database type (mysql, sqlite)
     *
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    public static $types = [];

    /**
     * Creates a new Database instance
     *
     * @param array $params
     * @return void
     */
    public function __construct(array $params = [])
    {
        $this->connect($params);
    }

    /**
     * Returns one of the started instances
     *
     * @param string|null $id
     * @return static|null
     */
    public static function instance(string $id = null)
    {
        return $id === null ? A::last(static::$connections) : static::$connections[$id] ?? null;
    }

    /**
     * Returns all started instances
     *
     * @return array
     */
    public static function instances(): array
    {
        return static::$connections;
    }

    /**
     * Connects to a database
     *
     * @param array|null $params This can either be a config key or an array of parameters for the connection
     * @return PDO|null
     * @throws InvalidArgumentException
     */
    public function connect(array $params = null)
    {
        $defaults = [
            'database' => null,
            'type' => 'mysql',
            'prefix' => null,
            'user' => null,
            'password' => null,
            'id' => uniqid()
        ];

        $options = array_merge($defaults, $params);

        // store the database information
        $this->database = $options['database'];
        $this->type = $options['type'];
        $this->prefix = $options['prefix'];
        $this->id = $options['id'];

        if (isset(static::$types[$this->type]) === false) {
            throw new InvalidArgumentException('Invalid database type: ' . $this->type);
        }

        // fetch the dsn and store it
        $this->dsn = static::$types[$this->type]['dsn']($options);

        // try to connect
        $this->connection = new PDO($this->dsn, $options['user'], $options['password']);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // store the connection
        static::$connections[$this->id] = $this;

        // return the connection
        return $this->connection;
    }

    /**
     * Returns the currently active connection
     *
     * @return PDO|null
     */
    public function connection(): ?PDO
    {
        return $this->connection;
    }

    /**
     * Sets the exception mode
     *
     * @param bool $fail
     * @return Database
     */
    public function fail(bool $fail = true)
    {
        $this->fail = $fail;
        return $this;
    }

    /**
     * Returns the used database type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Returns the used table name prefix
     *
     * @return string|null
     */
    public function prefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Escapes a value to be used for a safe query
     * NOTE: Prepared statements using bound parameters are more secure and solid
     *
     * @param string $value
     * @return string
     */
    public function escape(string $value): string
    {
        return substr($this->connection()->quote($value), 1, -1);
    }

    /**
     * Adds a value to the db trace and also returns the entire trace if nothing is specified
     *
     * @param array|null $data
     * @return array
     */
    public function trace(array $data = null): array
    {
        // return the full trace
        if ($data === null) {
            return $this->trace;
        }

        // add a new entry to the trace
        $this->trace[] = $data;

        return $this->trace;
    }

    /**
     * Returns the number of affected rows for the last query
     *
     * @return int|null
     */
    public function affected(): ?int
    {
        return $this->affected;
    }

    /**
     * Returns the last id if available
     *
     * @return int|null
     */
    public function lastId(): ?int
    {
        return $this->lastId;
    }

    /**
     * Returns the last query
     *
     * @return string|null
     */
    public function lastQuery(): ?string
    {
        return $this->lastQuery;
    }

    /**
     * Returns the last set of results
     *
     * @return mixed
     */
    public function lastResult()
    {
        return $this->lastResult;
    }

    /**
     * Returns the last db error
     *
     * @return Throwable
     */
    public function lastError()
    {
        return $this->lastError;
    }

    /**
     * Returns the name of the database
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->database;
    }

    /**
     * Private method to execute database queries.
     * This is used by the query() and execute() methods
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    protected function hit(string $query, array $bindings = []): bool
    {
        // try to prepare and execute the sql
        try {
            $this->statement = $this->connection->prepare($query);
            $this->statement->execute($bindings);

            $this->affected = $this->statement->rowCount();
            $this->lastId = Str::startsWith($query, 'insert ', true) ? $this->connection->lastInsertId() : null;
            $this->lastError = null;

            // store the final sql to add it to the trace later
            $this->lastQuery = $this->statement->queryString;
        } catch (Throwable $e) {

            // store the error
            $this->affected = 0;
            $this->lastError = $e;
            $this->lastId = null;
            $this->lastQuery = $query;

            // only throw the extension if failing is allowed
            if ($this->fail === true) {
                throw $e;
            }
        }

        // add a new entry to the singleton trace array
        $this->trace([
            'query' => $this->lastQuery,
            'bindings' => $bindings,
            'error' => $this->lastError
        ]);

        // return true or false on success or failure
        return $this->lastError === null;
    }

    /**
     * Executes a sql query, which is expected to return a set of results
     *
     * @param string $query
     * @param array $bindings
     * @param array $params
     * @return mixed
     */
    public function query(string $query, array $bindings = [], array $params = [])
    {
        $defaults = [
            'flag' => null,
            'method' => 'fetchAll',
            'fetch' => 'Modufolio\Toolkit\Obj',
            'iterator' => 'Modufolio\Toolkit\Collection',
        ];

        $options = array_merge($defaults, $params);

        if ($this->hit($query, $bindings) === false) {
            return false;
        }

        // define the default flag for the fetch method
        if ($options['fetch'] instanceof Closure || $options['fetch'] === 'array') {
            $flags = PDO::FETCH_ASSOC;
        } else {
            $flags = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
        }

        // add optional flags
        if (empty($options['flag']) === false) {
            $flags |= $options['flag'];
        }

        // set the fetch mode
        if ($options['fetch'] instanceof Closure || $options['fetch'] === 'array') {
            $this->statement->setFetchMode($flags);
        } else {
            $this->statement->setFetchMode($flags, $options['fetch']);
        }

        // fetch that stuff
        $results = $this->statement->{$options['method']}();

        // apply the fetch closure to all results if given
        if ($options['fetch'] instanceof Closure) {
            foreach ($results as $key => $result) {
                $results[$key] = $options['fetch']($result, $key);
            }
        }

        if ($options['iterator'] === 'array') {
            return $this->lastResult = $results;
        }

        return $this->lastResult = new $options['iterator']($results);
    }

    /**
     * Executes a sql query, which is expected to not return a set of results
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function execute(string $query, array $bindings = []): bool
    {
        return $this->lastResult = $this->hit($query, $bindings);
    }

    /**
     * Returns the correct Sql generator instance
     * for the type of database
     *
     * @return Sql
     */
    public function sql()
    {
        $className = static::$types[$this->type]['sql'] ?? 'Sql';
        return new $className($this);
    }

    /**
     * Sets the current table, which should be queried. Returns a
     * Query object, which can be used to build a full query
     * for that table
     *
     * @param string $table
     * @return Query
     */
    public function table(string $table)
    {
        return new Query($this, $this->prefix() . $table);
    }

    /**
     * Checks if a table exists in the current database
     *
     * @param string $table
     * @return bool
     */
    public function validateTable(string $table): bool
    {
        if ($this->tables === null) {
            // Get the list of tables from the database
            $sql = $this->sql()->tables($this->database);
            $results = $this->query($sql['query'], $sql['bindings']);

            if ($results) {
                $this->tables = $results->pluck('name');
            } else {
                return false;
            }
        }

        return in_array($table, $this->tables) === true;
    }

    /**
     * Checks if a column exists in a specified table
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function validateColumn(string $table, string $column): bool
    {
        if (isset($this->columnWhitelist[$table]) === false) {
            if ($this->validateTable($table) === false) {
                $this->columnWhitelist[$table] = [];
                return false;
            }

            // Get the column whitelist from the database
            $sql = $this->sql()->columns($table);
            $results = $this->query($sql['query'], $sql['bindings']);

            if ($results) {
                $this->columnWhitelist[$table] = $results->pluck('name');
            } else {
                return false;
            }
        }

        return in_array($column, $this->columnWhitelist[$table]) === true;
    }

    /**
     * Creates a new table
     *
     * @param string $table
     * @param array $columns
     * @return bool
     */
    public function createTable($table, $columns = []): bool
    {
        $sql = $this->sql()->createTable($table, $columns);
        $queries = Str::split($sql['query'], ';');

        foreach ($queries as $query) {
            $query = trim($query);

            if ($this->execute($query, $sql['bindings']) === false) {
                return false;
            }
        }

        // update cache
        if (in_array($table, $this->tables ?? []) !== true) {
            $this->tables[] = $table;
        }

        return true;
    }

    /**
     * Drops a table
     *
     * @param string $table
     * @return bool
     */
    public function dropTable(string $table): bool
    {
        $sql = $this->sql()->dropTable($table);
        if ($this->execute($sql['query'], $sql['bindings']) !== true) {
            return false;
        }

        // update cache
        $key = array_search($table, $this->tables ?? []);
        if ($key !== false) {
            unset($this->tables[$key]);
        }

        return true;
    }

    /**
     * Magic way to start queries for tables by
     * using a method named like the table.
     * I.e. $db->users()->all()
     *
     * @param mixed $method
     * @param mixed $arguments
     * @return Query
     */
    public function __call($method, $arguments = null)
    {
        return $this->table($method);
    }
}

/**
 * MySQL database connector
 */
Database::$types['mysql'] = [
    'sql' => 'Modufolio\Database\Sql\Mysql',
    'dsn' => function (array $params) {
        if (isset($params['host']) === false && isset($params['socket']) === false) {
            throw new InvalidArgumentException('The mysql connection requires either a "host" or a "socket" parameter');
        }

        if (isset($params['database']) === false) {
            throw new InvalidArgumentException('The mysql connection requires a "database" parameter');
        }

        $parts = [];

        if (empty($params['host']) === false) {
            $parts[] = 'host=' . $params['host'];
        }

        if (empty($params['port']) === false) {
            $parts[] = 'port=' . $params['port'];
        }

        if (empty($params['socket']) === false) {
            $parts[] = 'unix_socket=' . $params['socket'];
        }

        if (empty($params['database']) === false) {
            $parts[] = 'dbname=' . $params['database'];
        }

        $parts[] = 'charset=' . ($params['charset'] ?? 'utf8');

        return 'mysql:' . implode(';', $parts);
    }
];

/**
 * SQLite database connector
 */
Database::$types['sqlite'] = [
    'sql' => 'Modufolio\Database\Sql\Sqlite',
    'dsn' => function (array $params) {
        if (isset($params['database']) === false) {
            throw new InvalidArgumentException('The sqlite connection requires a "database" parameter');
        }

        return 'sqlite:' . $params['database'];
    }
];
