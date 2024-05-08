<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class LessThanIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_int_operator';
    }

    public static function name(): string
    {
        return 'Less than';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Less than';
    }

    /**
     * Checks if the given numeric value is less than the search numeric value.
     *
     * @param mixed $searchValue The search value to be compared against.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is less than the search value, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if both $searchValue and $value are numeric
        if(!is_numeric($searchValue) || !is_numeric($value)){
            return false; // If not, return false
        }

        // Convert $searchValue and $value to integers
        $searchValueInt = intval($searchValue);
        $valueInt = intval($value);

        // Compare $valueInt with $searchValueInt
        return $valueInt < $searchValueInt;
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
