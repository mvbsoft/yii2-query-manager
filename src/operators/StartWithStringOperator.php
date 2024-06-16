<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
use mvbsoft\queryManager\OperatorAbstract;

class StartWithStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'start_with_string_operator';
    }

    public static function name(): string
    {
        return 'Start with';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Start with';
    }

    /**
     * Checks if a string starts with a specified substring, ignoring case sensitivity.
     *
     * @param mixed $searchValue The substring to search for.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the string starts with the specified substring, false otherwise.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are scalar values
        if (!is_scalar($searchValue) || !is_scalar($value)) {
            return false;
        }

        // Convert $searchValue and $value to strings
        $searchValue = strval($searchValue);
        $value = strval($value);

        // Escape special characters
        $escapedSearchValue = preg_quote($searchValue, '/');

        // Check if $value starts with $searchValue using the regular expression pattern
        return preg_match("/^{$escapedSearchValue}/i", $value) === 1;
    }

    /**
     * Generate a condition array for MongoDB to perform a case-insensitive pattern match for strings starting with a specified substring.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The substring to search for.
     * @return array The condition array for MongoDB.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Initialize an empty condition array
        $condition = [];

        // Check if $searchValue is a scalar value
        if (!is_scalar($searchValue)) {
            return $condition; // Return empty condition if $searchValue is not scalar
        }

        // Convert $searchValue to a string
        $searchValue = strval($searchValue);

        $escapedSearchValue = preg_quote($searchValue, '/');

        // Construct the MongoDB condition for case-insensitive pattern match using regex
        return [$column => [
            '$regex' => "^" . $escapedSearchValue,
            '$options' => 'i'
        ]];
    }

    /**
     * Generate a condition array for the query builder to perform a case-insensitive pattern match.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is a scalar value
        if (!is_scalar($searchValue)) {
            return [];
        }

        // Convert $searchValue to a string
        $searchValue = strval($searchValue);

        // Construct the condition for case-insensitive pattern match using ILIKE
        return ['ILIKE', $column, "$searchValue%", false];
    }

}
