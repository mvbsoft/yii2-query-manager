<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class ContainsStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'contains_string_operator';
    }

    public static function name(): string
    {
        return 'Contains';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Contains';
    }

    /**
     * Checks if a substring exists within a string without considering case sensitivity.
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

        // Escape special characters
        $escapedSearchValue = preg_quote($searchValue, '/');
        $escapedValue = preg_quote($value, '/');

        // Check if $searchValue is found within $value using the regular expression without considering case sensitivity
        return preg_match("/{$escapedSearchValue}/i", $escapedValue) === 1;
    }

    public static function mongodbCondition($searchValue, string $column) : array
    {
        return [];
    }

    public static function postgresqlCondition($searchValue, string $column) : array
    {
        return [];
    }

}
