<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class IsNullOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'is_null_operator';
    }

    public static function name(): string
    {
        return 'Is null';
    }

    public static function group(): string
    {
        return parent::GROUP_DEFAULT;
    }

    public static function description(): string
    {
        return 'Is null';
    }

    /**
     * Checks if the given value is null.
     *
     * @param mixed $searchValue Not used in this function.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is null, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is null
        return is_null($value);
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
