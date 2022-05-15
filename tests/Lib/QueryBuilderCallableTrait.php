<?php

namespace DfTools\SlimOrm\Tests\Lib;

use DfTools\SlimOrm\QueryBuilder;

trait QueryBuilderCallableTrait
{

    private $notExtCallableMethods = [
        'buildSelectQuery',
        'buildDeleteQuery',
        'buildInsertQuery',
        'buildUpdateQuery',
        'getAndReturnFirstElement',
        'methodIsCallable',
    ];

    private $callableMethodsFromModel = [
        'update',
        'insert',
        'delete',
        'get',
        'first',
        'count',
        'max',
        'min',
        'paginate',
    ];

    /**
     * Generates the parameters to sent to the QueryBuilder's methods according to
     * the methods's argument types.
     *
     * @param string $methodName
     * @return array
     */
    private function getMethodParams(string $methodName): array
    {
        $reflectionMethod =  new \ReflectionMethod(QueryBuilder::class, $methodName);
     
        $params = [];

        foreach ($reflectionMethod->getParameters() as $reflectionType) {
            if (! $reflectionType) continue;

            if ($reflectionType->getType() == 'string') $params[] = 'a';
            else if ($reflectionType->getType() == 'int') $params[] = 1;
            else if ($reflectionType->getType() == 'array') $params[] = [];
            else if ($reflectionType->getType() == 'bool') $params[] = false;
            else $params[] = null;
        }

        return $params;
    }

    /**
     * Return the SELECT SQL query from a QueryBuilder object.
     *
     * @param QueryBuilder $query
     * @return string
     */
    private function buildSelectAndSanitize(QueryBuilder $query): string
    {
        return $this->sanitizeString($query->buildSelectQuery());
    }

    /**
     * Return the UPDATE SQL query from a QueryBuilder object.
     *
     * @param QueryBuilder $query
     * @param array $data
     * @return string
     */
    private function buildUpdateAndSanitize(QueryBuilder $query, array $data): string
    {
        return $this->sanitizeString($query->buildUpdateQuery($data));
    }

    /**
     * Return the INSERT SQL query from a QueryBuilder object.
     *
     * @param QueryBuilder $query
     * @param array $data
     * @return string
     */
    private function buildInsertAndSanitize(QueryBuilder $query, array $data): string
    {
        return $this->sanitizeString($query->buildInsertQuery($data));
    }

    /**
     * Return the DELETE SQL query from a QueryBuilder object.
     *
     * @param QueryBuilder $query
     * @return string
     */
    private function buildDeleteAndSanitize(QueryBuilder $query): string
    {
        return $this->sanitizeString($query->buildDeleteQuery());
    }

    /**
     * Remove the double spaces from a string and trim it.
     *
     * @param string $str
     * @return string
     */
    private function sanitizeString(string $str): string
    {
        return trim( preg_replace('/\s+/', ' ', $str) );
    }
    
}