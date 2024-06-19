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
     * Checks if the given value is neither null nor an empty string.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is neither null nor an empty string, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is neither null nor an empty string
        return !is_null($value) && $value !== '';
    }

    /**
     * Generate a condition array for MongoDB to check if a column is neither null nor an empty string.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used in this function.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Construct the condition for matching documents where the column value is neither null nor an empty string
        return [
            '$and' => [
                [$column => ['$ne' => '']],
                [$column => ['$ne' => null]],
                [$column => ['$exists' => true]]
            ]
        ];
    }

    /**
     * Generate a condition array for the query builder to check if a column is neither null nor an empty string in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        // Construct a condition array to check if the specified column is neither null nor an empty string
        return ["AND", ["IS NOT", $column, null], ["!=", $column, '']];
    }

}
