<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotContainsStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_contains_string_operator';
    }

    public static function name(): string
    {
        return 'Not contains';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not contains';
    }

    /**
     * Checks if a substring not exists within a string without considering case sensitivity.
     *
     * @param mixed $searchValue The substring to search for.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the substring is found within the string, false otherwise.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
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

        // Escape special characters in $searchValue
        $escapedSearchValue = preg_quote($searchValue, '/');

        // Create a regular expression for searching $searchValue within $value without considering case sensitivity
        $regex = "/{$escapedSearchValue}/i";

        // Check if $searchValue is not found within $value using the regular expression without considering case sensitivity
        return preg_match($regex, $value) !== 1;
    }

    public static function mongodbCondition($searchValue, $column) : array
    {
        return [];
    }

    public static function postgresqlCondition($searchValue, $column) : array
    {
        return [];
    }

}
