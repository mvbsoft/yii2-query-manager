<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class ContainsStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'contains_string_operator';
    }

    public static function name(): string
    {
        return 'Contains';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Contains';
    }

    public static function phpCondition($searchValue, string $column, $value): bool
    {
        return true;
    }

    public static function mongodbCondition($searchValue, string $column) : array
    {
        return [];
    }

    public static function postgresqlCondition($searchValue, string $column) : array
    {
        return [];
    }

}
