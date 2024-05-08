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
     * @param mixed $searchValue The value to be checked.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if both values are equal as strings, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
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

    public static function mongodbCondition($searchValue, $column) : array
    {
        return [];
    }

    public static function postgresqlCondition($searchValue, $column) : array
    {
        return [];
    }

}
