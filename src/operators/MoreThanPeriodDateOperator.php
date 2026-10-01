<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class MoreThanPeriodDateOperator extends OperatorAbstract
{
    public const PERIOD_YEARS = 'years';
    public const PERIOD_MONTHS = 'months';
    public const PERIOD_WEEKS = 'weeks';
    public const PERIOD_DAYS = 'days';
    public const PERIOD_HOURS = 'hours';
    public const PERIOD_MINUTES = 'minutes';
    public const PERIOD_SECONDS = 'seconds';

    public static function getPeriods(): array
    {
        return [
            self::PERIOD_YEARS,
            self::PERIOD_MONTHS,
            self::PERIOD_WEEKS,
            self::PERIOD_DAYS,
            self::PERIOD_HOURS,
            self::PERIOD_MINUTES,
            self::PERIOD_SECONDS,
        ];
    }

    public static function slug(): string
    {
        return 'more_than_period_date_operator';
    }

    public static function name(): string
    {
        return 'More than period';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'More than period';
    }

    /**
     * Checks if the value is a date after now + quantity * period
     * (see LessThanPeriodDateOperator::periodBoundary()).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $boundary = LessThanPeriodDateOperator::periodBoundary($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($boundary) || is_null($valueTimestamp)) {
            return false;
        }

        return $valueTimestamp > $boundary;
    }

    /**
     * Generate a condition array for MongoDB ("after the boundary second", whole-second precision like PHP).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $boundary = LessThanPeriodDateOperator::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$gte' => self::convertToMongoDate($boundary + 1)]];
    }

    /**
     * Generate a condition array for Postgres ("after the boundary second", whole-second precision like PHP).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $boundary = LessThanPeriodDateOperator::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::postgresqlMatchNothing();
        }

        return ['>=', $column, self::postgresqlTimestamp($boundary + 1)];
    }

    public static function convertPeriodToSeconds(string $period, int $quantity = 1)
    {
        return LessThanPeriodDateOperator::convertPeriodToSeconds($period, $quantity);
    }

}
