<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_int_operator';
    }

    public static function name(): string
    {
        return 'Not equal';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Not equal';
    }

    /**
     * Checks if the given numeric values are not equal after converting them to integers.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the numeric values are not equal after converting them to integers, otherwise returns false.
     */
    public static function phpCondition(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are numeric
        if (!is_numeric($searchValue) || !is_numeric($value)) {
            return false; // If not, return false
        }

        // Convert $searchValue and $value to integers
        $searchValueInt = intval($searchValue);
        $valueInt = intval($value);

        // Check if the integer values are not equal
        return $searchValueInt !== $valueInt;
    }

    public static function mongodbCondition($column, $searchValue) : array
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
    public static function postgresqlCondition(string $column, $searchValue): array
    {
        // Check if $searchValue is numeric
        if (!is_numeric($searchValue)) {
            return [];
        }

        // Construct the condition for matching the integer value in the column
        return ["!=", $column, intval($searchValue)];
    }

}
