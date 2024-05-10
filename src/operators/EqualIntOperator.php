<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class EqualIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'equal_int_operator';
    }

    public static function name(): string
    {
        return 'Equal';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Equal';
    }

    /**
     * Checks if two given values are equal numerically.
     *
     * @param mixed $searchValue The value to be checked.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if both values are equal numerically, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $searchValue and $value are numeric
        if (!is_numeric($searchValue) || !is_numeric($value)) {
            return false; // If not, return false
        }

        // Compare the numeric values of $searchValue and $value
        return intval($searchValue) === intval($value);
    }

    public static function mongodbConditions($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match an integer value in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The integer value to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is numeric
        if (!is_numeric($searchValue)) {
            return [];
        }

        // Construct the condition for matching the integer value in the column
        return [$column => intval($searchValue)];
    }

}
