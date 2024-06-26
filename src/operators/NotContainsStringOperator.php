<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
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

        // Escape special characters in $searchValue
        $escapedSearchValue = preg_quote($searchValue, '/');

        // Check if $searchValue is not found within $value using the regular expression without considering case sensitivity
        return preg_match("/{$escapedSearchValue}/i", $value) !== 1;
    }

    /**
     * Generate a condition array for MongoDB to perform a case-insensitive pattern match.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to search for.
     * @return array The condition array for MongoDB.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is a scalar value
        if (!is_scalar($searchValue)) {
            return [];
        }

        // Convert $searchValue to a string
        $searchValue = strval($searchValue);

        // Construct the MongoDB condition for case-insensitive pattern match using $not and $regex
        return [
            $column => [
                '$not' => new Regex($searchValue, 'i')
            ]
        ];
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

        // Construct the condition for case-insensitive pattern match using NOT ILIKE
        return ['NOT ILIKE', $column, $searchValue];
    }

}
