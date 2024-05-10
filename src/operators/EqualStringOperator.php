<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class EqualStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'equal_string_operator';
    }

    public static function name(): string
    {
        return 'Equal';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Equal';
    }

    /**
     * Checks if two given values are equal as strings.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The value to be checked.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if both values are equal as strings, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are scalar values (string, number, or boolean)
        if (!is_scalar($searchValue) || !is_scalar($value)) {
            return false; // If not, return false
        }

        // Compare the string representations of $searchValue and $value
        return strval($searchValue) === strval($value);
    }

    public static function mongodbConditions($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match a scalar value in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The scalar value to search for (string, number, or boolean).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Check if $searchValue is a scalar value (string, number, or boolean)
        if (!is_scalar($searchValue)) {
            return [];
        }

        // Convert $searchValue to a string
        $searchValue = strval($searchValue);

        // Construct the condition for matching the scalar value in the column
        return [$column => $searchValue];
    }

}
