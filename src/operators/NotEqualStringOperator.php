<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_string_operator';
    }

    public static function name(): string
    {
        return 'Not equal string';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not equal string';
    }

    /**
     * Checks if the given scalar values are not equal after converting them to strings.
     *
     * @param mixed $searchValue The search value to be compared against.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the scalar values are not equal after converting them to strings, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are scalar
        if (!is_scalar($searchValue) || !is_scalar($value)) {
            return false; // If not, return false
        }

        // Convert $searchValue and $value to strings and check if they are not equal
        return strval($searchValue) !== strval($value);
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
