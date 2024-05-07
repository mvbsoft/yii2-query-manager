<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_int_operator';
    }

    public static function name(): string
    {
        return 'Not equal';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Not equal';
    }

    public static function phpCondition($searchValue, string $column, $value): bool
    {
        return true;
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
