<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class InRangeStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'in_range_string_operator';
    }

    public static function name(): string
    {
        return 'In range';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'In range';
    }

    /**
     * Checks if a given value matches any of the string values in the provided array.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing string values to search for.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value matches any of the string values in the array, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is scalar
        if(!is_scalar($value)){
            return false; // If not, return false
        }

        // Convert $value to a string
        $valueString = strval($value);

        // Check if $searchValue is an array
        if(!is_array($searchValue)){
            return false; // If not, return false
        }

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item){
            // Skip non-scalar items
            if(!is_scalar($item)){
                continue; // Move to the next iteration
            }

            // Convert the item to a string
            $itemString = strval($item);

            // Check if $valueString matches $itemString
            if($valueString === $itemString){
                return true; // If so, return true
            }
        }

        return false; // If no match found, return false
    }

    /**
     * Generate a condition array for MongoDB to match an array of string values.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The array of values to search for.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is an array
        if (!is_array($searchValue)) {
            return [];
        }

        $searchValueStrings = [];

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-scalar items
            if (!is_scalar($item)) {
                continue; // Move to the next iteration
            }

            // Convert the item to a string
            $searchValueStrings[] = strval($item);
        }

        // If there are no valid scalar values in the array, return an empty array
        if (empty($searchValueStrings)) {
            return [];
        }

        // Construct the condition for matching the array of string values in the column
        // MongoDB query format is used here to match documents where the column value is in the searchValueStrings array
        return [$column => ['$in' => $searchValueStrings]];
    }


    /**
     * Generate a condition array for the query builder to match an array of string values in Postgres.
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

        $searchValueStrings = [];

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-scalar items
            if (!is_scalar($item)) {
                continue; // Move to the next iteration
            }

            // Convert the item to a string
            $searchValueStrings[] = strval($item);
        }

        // If there are no valid scalar values in the array, return an empty array
        if (empty($searchValueStrings)) {
            return [];
        }

        // Construct the condition for matching the array of string values in the column
        return [$column => $searchValueStrings];
    }

}
