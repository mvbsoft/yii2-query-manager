<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class IsNullOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'is_null_operator';
    }

    public static function name(): string
    {
        return 'Is null';
    }

    public static function group(): string
    {
        return parent::GROUP_DEFAULT;
    }

    public static function description(): string
    {
        return 'Is null';
    }

    /**
     * Checks if the given value is either null or an empty string.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is either null or an empty string, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is null or empty string
        return is_null($value) || $value === '';
    }

    /**
     * Generate a condition array for MongoDB to check if a column is null, an empty string, or does not exist.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used in this function.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Construct the condition for matching documents where the column value is null, an empty string, or the column does not exist
        return [
            '$or' => [
                [$column => ['$eq' => '']],
                [$column => ['$eq' => null]],
                [$column => ['$exists' => false]]
            ]
        ];
    }

    /**
     * Generate a condition array for the query builder to match a null value or an empty string in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Construct a condition array to match a null value or an empty string in the specified column
        return ['OR', [$column => null], [$column => '']];
    }

}
