<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class CurrentMonthOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'current_month_operator';
    }

    public static function name(): string
    {
        return 'Current month';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Current month';
    }

    /**
     * Checks if the given value represents today's date.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value represents today's date, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // TODO

        return true;
    }

    public static function mongodbConditions($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match the current date.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        // TODO

        return [];
    }

}
