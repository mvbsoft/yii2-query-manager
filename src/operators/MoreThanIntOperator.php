<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class MoreThanIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'more_than_int_operator';
    }

    public static function name(): string
    {
        return 'More than';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'More than';
    }

    /**
     * Checks if the given numeric value is greater than the search numeric value.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is greater than the search value, otherwise returns false.
     */
    public static function phpCondition(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are numeric
        if(!is_numeric($searchValue) || !is_numeric($value)){
            return false; // If not, return false
        }

        // Convert $searchValue and $value to integers
        $searchValueInt = intval($searchValue);
        $valueInt = intval($value);

        // Compare $valueInt with $searchValueInt
        return $valueInt > $searchValueInt;
    }

    public static function mongodbCondition($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to compare numeric values in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value to compare against (numeric).
     * @return array The condition array for the query.
     */
    public static function postgresqlCondition(string $column, $searchValue): array
    {
        // Check if $searchValue is numeric
        if (!is_numeric($searchValue)) {
            return [];
        }

        // Construct a condition array to compare the column with the numeric search value
        return ['<', $column, intval($searchValue)];
    }

}
