<?php

namespace DfTools\SlimOrm;

abstract class Model
{
    
    /**
     * Store the name of the database table that the model represents.
     *
     * @var string
     */
    protected $table = '';
    
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Hold the value of the primary key loaded in the model object.
     *
     * @var mixed
     */
    protected $primaryKeyValue = null;

    /**
     * Store the data coming from the database.
     *
     * @var array
     */
    protected $data = [];

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
            QueryBuilder::methodIsCallable($method, true)
        ) {
            return static::query()->$method(...$args);
        }

        throw new SlimOrmException('Call to undefined method ' . static::class . '::' . $method . '()');
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
        if (! DB::getInstance()) {
            throw new SlimOrmException('The DB instance has not been initialized.');
        }

        if (
            method_exists(QueryBuilder::class, $method) and
            QueryBuilder::methodIsCallable($method, true)
        ) {
            return static::query()->$method(...$args);
        }

        throw new SlimOrmException('Call to undefined static method ' . static::class . '::' . $method . '()');
    }

    /**
     * Getter magic method: return the value stored in the "data" array if present;
     * null otherwise.
     *
     * @param string $name
     * @return void
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Setter magic method: set the given key/value in the data array.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Isset magic method: check if the given key is set in the "data" array.
     *
     * @param string $name
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Unset magic method: unset the given key from the "data" array.
     *
     * @param string $name
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    /**
     * Constructor. 
     *
     * @param array $data
     */
    public function __construct(array $data=[])
    {
        $this->data = $data;
    }

    /**
     * Find a model element from the database and return the model instance (if
     * found).
     *
     * @param mixed $value
     * @param string|null $findByField
     * @return self|null
     */
    public static function find($value, string $findByField=null): ?self
    {
        $instance = new static;

        return $instance->where($findByField ?? $instance->primaryKey, '=', $value)->first();
    }

    /**
     * Initialize the model instance: set the data and the primary key value.
     *
     * @param array|null $data
     * @throws SlimOrmException
     * @return self
     */
    private function initModelData(array $data=null): self
    {
        if ($data) $this->data = $data;

        if (! isset($this->data[$this->primaryKey])) {
            throw new SlimOrmException('Primary key not provided.');
        }

        $this->primaryKeyValue = $this->data[$this->primaryKey];

        return $this;
    }

    /**
     * Initialize the model instance on a static way (this is just a wrapper
     * of the non-static function init).
     *
     * @param array $data
     * @return self
     */
    public static function initStatic(array $data): self
    {
        $instance = new static;

        return $instance->initModelData($data);
    }

    /**
     * Save the model data: it updates the instance if it has been initialized
     * before. It creates a new instance if the object has not been initialized
     * before.
     *
     * @return void
     */
    public function save()
    {
        if ($this->primaryKeyValue) {
            $this->where($this->primaryKey, '=', $this->primaryKeyValue)
                ->update($this->data);
            
            if ($this->primaryKeyValue !== $this->data[$this->primaryKey]) {
                $this->primaryKeyValue = $this->data[$this->primaryKey];
            }

            return;
        }

        $this->data[$this->primaryKey] = DB::table($this->table)->insert($this->data);

        $this->initModelData();
    }

    /**
     * Return the data property value.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return a new query builder instance.
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return (new QueryBuilder(DB::getInstance(), static::class))
            ->table((new static)->table);
    }

}