<?php

namespace DfTools\SlimOrm;

/**
 * Class for building and executing queries.
 */
class QueryBuilder
{

    /**
     * Sore the DB instance.
     *
     * @var DB
     */
    private $db;

    /**
     * Store the name of the eventual model for casting the resultset.
     *
     * @var string
     */
    private $modelClassCast;

    /**
     * Store all the elements to build the query.
     *
     * @var array
     */
    private $query = [
        'select' => '*',
        'table' => '',
        'join' => '',
        'where' => '',
        'order' => '',
        'group' => null,
        'limit' => null,
        'offset' => null,
    ];

    /**
     * Store the data that have to be bind into the query.
     *
     * @var array
     */
    private $data = [];

    /**
     * Constructor.
     *
     * @param DB $db
     * @param string $modelClassCast
     */
    public function __construct(
        DB $db,
        string $modelClassCast=null,
    ) {
        $this->db = $db;
        $this->modelClassCast = $modelClassCast;
    }

    private static $notExtCallableMethods = [
        'buildSelectQuery',
        'buildDeleteQuery',
        'buildInsertQuery',
        'buildUpdateQuery',
        'getAndReturnFirstElement',
        'methodIsCallable',
    ];

    private static $callableMethodsFromModel = [
        'update',
        'insert',
        'delete',
        'get',
        'first',
        'create',
        'count',
        'max',
        'min',
        'paginate',
    ];

    /**
     * Check if the given method can be called from the DB instance.
     * 
     * From the DB object you can call a function from the QueryBuilder for example
     * DB::table('my-table)->where(...)
     * In that example the function "table" is declared in QueryBuilder but called
     * through the DB class. Not all the functions of this class can be called
     * through the DB class.
     *
     * @param string $method
     * @param bool $isModel
     * @return boolean
     */
    public static function methodIsCallable(string $method, bool $isModel=false): bool
    {
        if ($isModel) {
            return !in_array($method, static::$notExtCallableMethods);
        }

        return !in_array($method, array_merge(
            static::$notExtCallableMethods,
            static::$callableMethodsFromModel,
        ));
    }

    /**
     * Set the SELECT statement into the query.
     *
     * @param string $select
     * @return self
     */
    public function select(string $select): self
    {
        if ($this->query['select'] == '*') $this->query['select'] = '';

        $this->query['select'] .= " $select ";

        return $this;
    }

    /**
     * Set the database table into the FROM statement of the query.
     *
     * @param string $table
     * @return self
     */
    public function table(string $table): self
    {
        $table = str_replace('`', '', $table);

        $this->query['table'] .= " `$table` ";

        return $this;
    }

    /**
     * Add the join statement into the query.
     *
     * @param string $table
     * @param string $leftCol
     * @param string $operator
     * @param string $rightCol
     * @param string $joinType
     * @return self
     */
    public function join(string $table, string $leftCol, string $operator, string $rightCol, string $joinType=''): self
    {
        $this->query['join'] .= " $joinType JOIN `$table` ON  $leftCol $operator $rightCol";

        return $this;
    }

    /**
     * Add the left join statement into the query.
     *
     * @param string $table
     * @param string $leftCol
     * @param string $operator
     * @param string $rightCol
     * @return self
     */
    public function leftJoin(string $table, string $leftCol, string $operator, string $rightCol): self
    {
        return $this->join($table, $leftCol, $operator, $rightCol, 'LEFT');
    }

    /**
     * Add the right join statement into the query.
     *
     * @param string $table
     * @param string $leftCol
     * @param string $operator
     * @param string $rightCol
     * @return self
     */
    public function rightJoin(string $table, string $leftCol, string $operator, string $rightCol): self
    {
        return $this->join($table, $leftCol, $operator, $rightCol, 'RIGHT');
    }

    /**
     * Add the given where statement (freely defined) into the query.
     *
     * @param string $where
     * @param array $data
     * @return self
     */
    public function whereRaw(string $where, array $data=[]): self
    {
        $this->query['where'] .= " $where ";

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Add the where statement into the query.
     *
     * You can pass an anonymous function for the parameter $columnOrClosure
     * to make nested AND/OR statements.
     * 
     * @param string|Closure $columnOrClosure
     * @param string|null $operatorOrValue
     * @param mixed $value
     * @param string $andOr
     * @return self
     */
    public function where($columnOrClosure, string $operatorOrValue=null, $value=null, string $andOr='AND'): self
    {
        $andOr = strtoupper($andOr);

        if ($columnOrClosure instanceof \Closure) {
            return $this->handleClosure($columnOrClosure, $andOr);
        }

        if ($operatorOrValue != '=' and $value === null) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        // in this case, $columnOrClosure contains the name of the column
        if ($this->query['where'] !== '') $this->query['where'] .= " $andOr ";

        $key = (count($this->data) + 1) . '_sql_data';

        $this->query['where'] .= " `$columnOrClosure` $operatorOrValue :$key ";
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Process the closure if an anonymous function has been given to the where.
     *
     * @param \Closure $closure
     * @param string $andOr
     * @return self
     */
    private function handleClosure(\Closure $closure, string $andOr='AND'): self
    {
        if ($this->query['where'] !== '') $this->query['where'] .= " $andOr ";

        $subQuery = new static(DB::getInstance());
        $closure($subQuery);

        $sql = $subQuery->query['where'];
        $dataKey = (count($this->data) + 1);

        foreach ($subQuery->data as $key => $val) {
            $newKey = $dataKey . '_' . $key . '_sub_q';
            $sql = str_replace(":$key", ":$newKey", $sql);
            $this->data[$newKey] = $val;
        }

        $this->query['where'] .= " ( $sql ) ";

        return $this;
    }

    /**
     * Add an OR where statement into the query.
     * 
     * You can pass an anonymous function for the parameter $columnOrClosure
     * to make nested AND/OR statements.
     *
     * @param string|Closure $columnOrClosure
     * @param string|null $operatorOrValue
     * @param mixed $value
     * @return self
     */
    public function orWhere($columnOrClosure, string $operatorOrValue=null, $value=null): self
    {
        return $this->where($columnOrClosure, $operatorOrValue, $value, 'OR');
    }

    /**
     * Add a WHERE field IS NULL statement into the query.
     *
     * @param string $field
     * @param string $andOr
     * @return self
     */
    public function whereNull(string $field, string $andOr='AND'): self
    {
        if ($this->query['where'] !== '') $this->query['where'] .= " " . strtoupper($andOr) . " ";

        $this->query['where'] .= " `$field` IS NULL ";

        return $this;
    }

    /**
     * Add a OR WHERE field IS NULL statement into the query.
     *
     * @param string $field
     * @param string $andOr
     * @return self
     */
    public function orWhereNull(string $field): self
    {
        return $this->whereNull($field, 'OR');
    }

    /**
     * Add the limit statement into the query.
     *
     * @param integer $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    /**
     * Add the offset statement into the query.
     *
     * @param integer $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->query['offset'] = $offset;

        return $this;
    }
    
    /**
     * Add the given columns to the ORDER BY statement.
     *
     * @param string ...$columns
     * @return self
     */
    public function orderBy(...$columns): self
    {
        $sql = ($this->query['order'] === '') ? '' : ',';

        foreach ($columns as $key => $item) {
            $itemUppercase = strtoupper(trim($item));

            if (in_array($itemUppercase, ['ASC', 'DESC'])) {
                $sql .= " $itemUppercase,";
                continue;
            }
            
            $separator = ',';

            if (
                isset($columns[$key+1]) and
                in_array(strtoupper(trim($columns[$key+1])), ['ASC', 'DESC'])
            ) {
                $separator = '';
            }
            
            $sql .= " `$item` " . $separator;
        }

        $this->query['order'] .= rtrim($sql, ',');

        return $this;
    }

    /**
     * Set the ascending order for the given column.
     *
     * @param string $column
     * @return self
     */
    public function orderByAsc(string $column): self
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Set the descending order for the given column.
     *
     * @param string $column
     * @return self
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add the group by statement into the query.
     *
     * @param string ...$columns
     * @return self
     */
    public function groupBy(...$columns): self
    {
        $columns = array_map(fn($col) => "`$col`", $columns);
        
        $this->query['group'] = implode(', ', $columns);

        return $this;
    }

    /**
     * Build up the select query.
     *
     * @return string
     */
    public function buildSelectQuery(): string
    {
        $sql  = "SELECT " . $this->query['select'] . "\n";

        if ($this->query['table'] !== '') {        
            $sql .= "FROM "   . $this->query['table']   . "\n";
        }

        if ($this->query['join'] !== '') {        
            $sql .= $this->query['join']   . "\n";
        }

        if ($this->query['where'] !== '') {
            $sql .= "WHERE " . $this->query['where'] . "\n";
        }

        if ($this->query['group'] !== null) {
            $sql .= "GROUP BY " . $this->query['group'] . "\n";
        }

        if ($this->query['order'] !== '') {
            $sql .= "ORDER BY " . $this->query['order'] . "\n";
        }

        if ($this->query['limit'] !== null) {
            $sql .= "LIMIT " . $this->query['limit'] . "\n";
        }

        if ($this->query['offset'] !== null) {
            $sql .= "OFFSET " . $this->query['offset'] . "\n";
        }

        return $sql;
    }

    /**
     * Build the UPDATE query and execute it.
     *
     * @param array $data
     * @param bool $whereMandatory
     * @throws SlimOrmException
     * @return void
     */
    public function update(array $data, bool $whereMandatory=true)
    {
        if ($whereMandatory and $this->query['where'] === '') {
            throw new SlimOrmException('The where clause is mandatory in the UPDATE statement.');
        }

        $sql = $this->buildUpdateQuery($data);

        return $this->db->execStatement($sql, array_merge($data, $this->data));
    }

    /**
     * Build the SQL query for the update statement.
     *
     * @param array $data
     * @return string
     */
    public function buildUpdateQuery(array $data): string
    {
        $sql  = "UPDATE " . $this->query['table'] . "\nSET ";

        array_walk($data, function($val, $key) use (&$sql) {
            $sql .= " `$key` = :$key ,";
        }, []);

        $sql = rtrim($sql, ',') . "\n";

        if ($this->query['where'] !== '') {
            $sql .= "WHERE " . $this->query['where'] . "\n";
        }

        return $sql;
    }

    /**
     * Build the INSERT query, execute it and it returns the last insert ID.
     *
     * @param array $data
     * @return mixed
     */
    public function insert(array $data)
    {
        $sql = $this->buildInsertQuery($data);

        $this->db->execStatement($sql, $data);

        return $this->db->pdo()->lastInsertId();
    }

    /**
     * Build the SQL query for the insert statement.
     *
     * @param array $data
     * @return string
     */
    public function buildInsertQuery(array $data): string
    {
        $sql  = "INSERT INTO " . $this->query['table'] . "\n";

        $fields = $values = '';

        foreach ($data as $field => $value) {
            $fields .= " `$field` ,";
            $values .= " :$field ,";
        }

        $fields = rtrim($fields, ',');
        $values = rtrim($values, ',');

        $sql .= "(" . $fields . ") VALUES (" . $values . ")";

        return $sql;
    }

    /**
     * Build the DELETE query and execute it.
     *
     * @return void
     */
    public function delete()
    {
        $sql = $this->buildDeleteQuery();

        return $this->db->execStatement($sql, $this->data);
    }

    /**
     * Build the SQL query for the delete statement.
     *
     * @return string
     */
    public function buildDeleteQuery(): string
    {
        $sql  = "DELETE FROM " . $this->query['table'] . "\n";

        if ($this->query['where'] !== '') {
            $sql .= "WHERE " . $this->query['where'] . "\n";
        }

        return $sql;
    }

    /**
     * Execute the query.
     *
     * @return void
     */
    public function get(): array
    {        
        return $this->performQueryGet(true);
    }

    /**
     * Execute the query and cast the result set into the model class, eventually.
     *
     * @param boolean $castToModelClass
     * @return array
     */
    private function performQueryGet(bool $castToModelClass=false): array
    {
        $result = $this->db->query($this->buildSelectQuery(), $this->data);

        if (count($result) and $this->modelClassCast and $castToModelClass) {
            return array_map(fn($i) => $this->modelClassCast::initStatic((array)$i), $result);
        }

        return $result;
    }

    /**
     * Execute the query and return the first element of the result.
     *
     * @return stdClass|Model|null
     */
    public function first()
    {
        $this->limit(1);
        
        return $this->getAndReturnFirstElement(true);
    }

    /**
     * Execute the count statement.
     *
     * @return integer
     */
    public function count(): int
    {
        $this->query['select'] = "COUNT(*) as `count_aggregate`";

        $record = $this->getAndReturnFirstElement();

        return $record ? (int)$record->count_aggregate : 0;
    }

    /**
     * Return the max value for the given database column.
     *
     * @param string $column
     * @return void
     */
    public function max(string $column)
    {
        $this->query['select'] = "MAX(`$column`) as `max_aggregate`";
        
        $record = $this->getAndReturnFirstElement();

        return $record ? $record->max_aggregate : null;
    }

    /**
     * Return the min value for the given database column.
     *
     * @param string $column
     * @return void
     */
    public function min(string $column)
    {
        $this->query['select'] = "MIN(`$column`) as `min_aggregate`";
        
        $record = $this->getAndReturnFirstElement();

        return $record ? $record->min_aggregate : null;
    }

    /**
     * Execute the get and return the first value from the result set.
     *
     * @param bool $castToModelClass
     * @return stdClass|null
     */
    private function getAndReturnFirstElement(bool $castToModelClass=false)
    {
        $res = $this->performQueryGet($castToModelClass);

        if (count($res)) return $res[0];

        return null;
    }

    /**
     * Return the result set grouped in pages.
     *
     * @param integer $perPage
     * @return void
     */
    public function paginate(int $perPage=20)
    {
        $page = (int)($_GET['page'] ?? 1);
        if ($page <= 0) $page = 1;

        // getting the total of elements
        $this->query['limit'] = null;
        $this->query['offset'] = null;
        $total = (clone $this)->count();

        $lastPage = (int)ceil($total / $perPage);
        if ($page > $lastPage) $page = $lastPage;

        // getting the elements in the current page
        $this->limit($perPage);
        $this->offset($perPage * ($page - 1));
        $data = $this->get();

        if (! $total) {
            $from = $to = 0;
        } else {
            $from = $perPage * ($page - 1) + 1;
            $to = $from + $perPage - 1;
            if ($from < 0) $from = 0;
            if ($to > $total) $to = $total;
        }
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ];
    }

}