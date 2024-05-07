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
