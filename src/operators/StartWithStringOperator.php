<?php

namespace mvbsoft\queryManager\operators;

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
    public static function phpCondition(string $column, $searchValue, array $data): bool
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
        $escapedValue = preg_quote($value, '/');

        // Check if $value starts with $searchValue using the regular expression pattern
        return preg_match("/^{$escapedSearchValue}/i", $escapedValue) === 1;
    }

    public static function mongodbCondition($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to perform a case-insensitive pattern match.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlCondition(string $column, $searchValue): array
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
