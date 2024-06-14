<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class IsNotNullOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'is_not_null_operator';
    }

    public static function name(): string
    {
        return 'Is not null';
    }

    public static function group(): string
    {
        return parent::GROUP_DEFAULT;
    }

    public static function description(): string
    {
        return 'Is not null';
    }

    /**
     * Checks if the given value is not null.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is not null, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is not null
        return !is_null($value);
    }

    /**
     * Generate a condition array for MongoDB to check if a column is not null and exists.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used in this function.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Construct the condition for matching documents where the column value is not null and the column exists
        // $ne ensures the value is not null
        // $exists ensures the field is present in the document
        return [
            $column => [
                '$ne' => null,
                '$exists' => true
            ]
        ];
    }

    /**
     * Generate a condition array for the query builder to check if a column is not null in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        // Construct a condition array to check if the specified column is not null
        return ['IS NOT', $column, null];
    }

}
