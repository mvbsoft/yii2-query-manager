<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotInRangeIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'in_range_int_operator';
    }

    public static function name(): string
    {
        return 'In range';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'In range';
    }

    /**
     * Checks if a given value does not match any of the numeric values in the provided array.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing numeric values to search for.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value does not match any of the numeric values in the array, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is numeric
        if(!is_numeric($value)){
            return false;
        }

        // Convert $value to an integer
        $valueInt = intval($value);

        // Check if $searchValue is an array
        if(!is_array($searchValue)){
            return false;
        }

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item){
            // Skip non-numeric items
            if(!is_numeric($item)){
                continue; // Move to the next iteration
            }

            // Convert the item to an integer
            $itemInt = intval($item);

            // Check if $valueInt matches $itemInt
            if($valueInt === $itemInt){
                return false; // Value found in array, return false
            }
        }

        return true; // Value not found in array, return true
    }

    /**
     * Generate a condition array for MongoDB to exclude an array of integer values.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The array of integer values to exclude.
     * @return array The condition array for MongoDB.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Initialize an empty condition array
        $condition = [];

        // Check if $searchValue is an array
        if (!is_array($searchValue)) {
            return $condition; // Return empty condition if $searchValue is not an array
        }

        $searchIntValues = [];

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-numeric items
            if (!is_numeric($item)) {
                continue;
            }

            // Convert the item to an integer and add to $searchIntValues
            $searchIntValues[] = intval($item);
        }

        // If there are no valid numeric values in the array, return an empty array
        if (empty($searchIntValues)) {
            return $condition;
        }

        // Build the MongoDB condition to exclude the array of integers using $nin operator
        return [$column => ['$nin' => $searchIntValues]];
    }
    /**
     * Generate a condition array for the query builder to match an array of integers in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The array of values to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is an array
        if (!is_array($searchValue)) {
            return [];
        }

        $searchIntValues = [];

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-numeric items
            if (!is_numeric($item)) {
                continue;
            }

            // Convert the item to an integer
            $searchIntValues[] = intval($item);
        }

        // If there are no valid numeric values in the array, return an empty array
        if (empty($searchIntValues)) {
            return [];
        }

        // Construct the condition for matching the array of integers in the column
        return ["NOT IN", $column, $searchIntValues];
    }

}
