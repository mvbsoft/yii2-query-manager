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

    public static function phpCondition($searchValue, string $column, $value): bool
    {
        if (!is_scalar($searchValue) || !is_scalar($value)) {
            return false;
        }

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
