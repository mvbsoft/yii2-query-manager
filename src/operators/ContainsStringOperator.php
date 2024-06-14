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
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The substring to search for.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the substring is found within the string, false otherwise.
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

        return  mb_strpos($value, $searchValue) !== false;
    }

    public static function mongodbConditions(string $column, $searchValue) : array
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
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is a scalar value
        if (!is_scalar($searchValue)) {
            return [];
        }

        // Convert $searchValue to a string
        $searchValue = strval($searchValue);

        // Construct the condition for case-insensitive pattern match using ILIKE
        return ['ILIKE', $column, $searchValue];
    }

}
