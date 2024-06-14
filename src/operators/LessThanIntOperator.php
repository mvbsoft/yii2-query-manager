<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class LessThanIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_int_operator';
    }

    public static function name(): string
    {
        return 'Less than';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Less than';
    }

    /**
     * Checks if the given numeric value is less than the search numeric value.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is less than the search value, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are numeric
        if(!is_numeric($searchValue) || !is_numeric($value)){
            return false; // If not, return false
        }

        // Compare $valueInt with $searchValueInt
        return intval($value) < intval($searchValue);
    }

    /**
     * Generate a condition array for MongoDB to check if a column value is less than the specified numeric value.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value to compare against (should be numeric).
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is numeric
        if (!is_numeric($searchValue)) {
            return [];
        }

        // Construct the condition for matching documents where the column value is less than the specified numeric value
        return [
            $column => ['$lt' => intval($searchValue)]
        ];
    }

    /**
     * Generate a condition array for the query builder to compare numeric values in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value to compare against (numeric).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is numeric
        if (!is_numeric($searchValue)) {
            return [];
        }

        // Construct a condition array to compare the column with the numeric search value
        return ['<', $column, intval($searchValue)];
    }

}
