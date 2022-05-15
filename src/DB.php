<?php

namespace DfTools\SlimOrm;

final class DB
{
    
    /**
     * Keep the PDO object.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Store the self DB instance for static call.
     *
     * @var DB
     */
    private static $instance = null;

    /**
     * Initialize the PDO database connection.
     *
     * @param array $config
     * @return void
     */
    private function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;

        static::$instance = $this;
    }

    /**
     * Initialize the DB object.
     *
     * @param \PDO $pdo
     * @return DB
     */
    public static function init(\PDO $pdo): self
    {
        if (! static::$instance) {
            new static($pdo);
        }
        
        return static::$instance;
    }

    /**
     * Return the DB instance.
     *
     * @return DB
     */
    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    /**
     * Invoke a method in a non-static way.
     *
     * @param string $method
     * @param array $args
     * @throws SlimOrmException
     * @return void
     */
    public function __call(string $method, array $args=[])
    {
        if (
            method_exists(QueryBuilder::class, $method) and
            QueryBuilder::methodIsCallable($method)
        ) {
            $queryBuilder = new QueryBuilder($this);

            return $queryBuilder->$method(...$args);
        }

        throw new SlimOrmException('Call to undefined method DB::' . $method . '()');
    }

    /**
     * Invoke a method in a static way.
     *
     * @param string $method
     * @param array $args
     * @throws SlimOrmException
     * @return void
     */
    public static function __callStatic(string $method, array $args=[])
    {
        if (! static::$instance) {
            throw new SlimOrmException('The DB instance has not been initialized.');
        }

        if (
            method_exists(QueryBuilder::class, $method) and
            QueryBuilder::methodIsCallable($method)
        ) {
            $queryBuilder = new QueryBuilder(static::getInstance());

            return $queryBuilder->$method(...$args);
        }

        throw new SlimOrmException('Call to undefined static method DB::' . $method . '()');
    }

    /**
     * Execute the given SQL statement.
     *
     * @param string $sql
     * @param array $data
     * @return PDOStatement
     */
    public function execStatement(string $sql, array $data=[]): \PDOStatement
    {
        if (empty($data)) {
            return $this->pdo->query($sql);
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return $statement;
    }

    /**
     * Execute the given SQL query.
     *
     * @param string $sql
     * @param array $data
     * @return array
     */
    public function query(string $sql, array $data=[]): array
    {
        return $this->execStatement($sql, $data)
            ->fetchAll(\PDO::FETCH_CLASS);
    }

    /**
     * Return the PDO instance.
     *
     * @return \PDO
     */
    public function pdo(): \PDO
    {
        return $this->pdo;
    }

}