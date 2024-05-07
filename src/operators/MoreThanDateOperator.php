<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class MoreThanDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'more_than_date_operator';
    }

    public static function name(): string
    {
        return 'More than';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'More than';
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
