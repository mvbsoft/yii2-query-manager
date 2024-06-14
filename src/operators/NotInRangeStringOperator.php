<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotInRangeStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_in_range_string_operator';
    }

    public static function name(): string
    {
        return 'Not in range';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not in range';
    }

    /**
     * Checks if a given value does not match any of the string values in the provided array.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing string values to search for.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value does not match any of the string values in the array, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is scalar
        if (!is_scalar($value)) {
            return true; // If not, return true because non-scalar value cannot match
        }

        // Convert $value to a string
        $valueString = strval($value);

        // Check if $searchValue is an array
        if (!is_array($searchValue)) {
            return true; // If not, return true because searchValue is invalid
        }

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-scalar items
            if (!is_scalar($item)) {
                continue; // Move to the next iteration
            }

            // Convert the item to a string
            $itemString = strval($item);

            // Check if $valueString matches $itemString
            if ($valueString === $itemString) {
                return false; // If a match is found, return false
            }
        }

        return true; // If no match is found, return true
    }

    /**
     * Generate a condition array for MongoDB to exclude an array of string values.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The array of string values to exclude.
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

        $searchStringValues = [];

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item) {
            // Skip non-scalar items
            if (!is_scalar($item)) {
                continue;
            }

            // Convert the item to a string and add to $searchStringValues
            $searchStringValues[] = strval($item);
        }

        // If there are no valid string values in the array, return an empty array
        if (empty($searchStringValues)) {
            return $condition;
        }

        // Build the MongoDB condition to exclude the array of string values using $nin operator
        return [$column => ['$nin' => $searchStringValues]];
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
        return ["NOT IN", $column, $searchValueStrings];
    }

}
