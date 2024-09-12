<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class IsTrueBooleanOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'is_true_boolean_operator';
    }

    public static function name(): string
    {
        return 'Is true';
    }

    public static function group(): string
    {
        return parent::GROUP_BOOLEAN;
    }

    public static function description(): string
    {
        return 'Is true';
    }

    /**
     * Checks if the given value is a scalar and evaluates to true.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is a scalar and evaluates to true, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is a scalar
        if(!is_scalar($value)){
            return false; // If not, return false
        }

        // Convert $value to boolean and check if it is true
        return boolval($value) === true;
    }

    /**
     * Generate a condition array for MongoDB to check if a column is true.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used in this function.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue = null): array
    {
        // Construct the condition for matching documents where the column value is true
        return [$column => ['$eq' => true]];
    }

    /**
     * Generate a condition array for the query builder to match a boolean true value in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Construct a condition array to match a boolean true value in the specified column
        return [$column => true];
    }

}
