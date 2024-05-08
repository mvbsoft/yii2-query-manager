<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class EndWithStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'end_with_string_operator';
    }

    public static function name(): string
    {
        return 'End with';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'End with';
    }

    /**
     * Checks if a string ends with a specified substring, ignoring case sensitivity.
     *
     * @param mixed $searchValue The substring to search for.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the string ends with the specified substring, false otherwise.
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

        // Check if $value ends with $searchValue using the regular expression pattern
        return preg_match("/{$escapedSearchValue}$/i", $escapedValue) === 1;
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
