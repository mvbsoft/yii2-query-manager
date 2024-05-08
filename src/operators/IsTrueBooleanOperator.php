<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class IsTrueBooleanOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'is_true_boolean_operator';
    }

    public static function name(): string
    {
        return 'Is true';
    }

    public static function group(): string
    {
        return parent::GROUP_BOOLEAN;
    }

    public static function description(): string
    {
        return 'Is true';
    }

    /**
     * Checks if the given value is a scalar and evaluates to true.
     *
     * @param mixed $searchValue Not used in this function.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is a scalar and evaluates to true, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is a scalar
        if(!is_scalar($value)){
            return false; // If not, return false
        }

        // Convert $value to boolean and check if it is true
        return boolval($value) === true;
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
