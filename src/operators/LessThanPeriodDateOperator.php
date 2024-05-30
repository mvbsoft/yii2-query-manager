<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class LessThanPeriodDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_period_date_operator';
    }

    public static function name(): string
    {
        return 'Less than period';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Less than period';
    }

    /**
     * Checks if the given value is less than the search value after converting them to timestamps.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is less than the search value after converting them to timestamps, otherwise returns false.
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
     * Generate a condition array for the query builder to match timestamps in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value to compare against (can be a timestamp or any format convertible to a timestamp).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // TODO

        return [];
    }

}
