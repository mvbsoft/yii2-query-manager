<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;

class CurrentDayOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'current_day_operator';
    }

    public static function name(): string
    {
        return 'Current day';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Current day';
    }

    /**
     * Start and end (inclusive) timestamps of today in the PHP default timezone.
     *
     * The same bounds are used by all three backends (PostgreSQL does not use its own current_date,
     * which depends on the database clock and session timezone).
     *
     * @return array ['from' => int, 'to' => int]
     */
    public static function range(): array
    {
        return self::dayRange(Carbon::now()->timestamp);
    }

    /**
     * Checks if the value is a date of today.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue Not used.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($valueTimestamp)) {
            return false;
        }

        $range = self::range();

        return $valueTimestamp >= $range['from'] && $valueTimestamp <= $range['to'];
    }

    /**
     * Generate a condition array for MongoDB to match the current day.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $range = self::range();

        return self::mongodbDateRange($column, $range['from'], $range['to']);
    }

    /**
     * Generate a condition array for Postgres to match the current day.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Not used.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        $range = self::range();

        return self::postgresqlDateRange($column, $range['from'], $range['to']);
    }
}
