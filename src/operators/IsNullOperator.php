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
     * Checks if the given value is null.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is null, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is null
        return is_null($value);
    }

    /**
     * Generate a condition array for MongoDB to check if a column is null or does not exist.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used in this function.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Construct the condition for matching documents where the column value is null or the column does not exist
        // $eq ensures the value is null
        // $exists set to false ensures the field is not present in the document
        return [
            '$or' => [
                [$column => ['$eq' => null]],
                [$column => ['$exists' => false]]
            ]
        ];
    }

    /**
     * Generate a condition array for the query builder to match a null value in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Construct a condition array to match a null value in the specified column
        return [$column => null];
    }

}
