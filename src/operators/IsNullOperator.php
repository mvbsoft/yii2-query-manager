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
