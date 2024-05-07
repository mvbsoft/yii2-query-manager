<?php

namespace mvbsoft\queryManager\operators;

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
