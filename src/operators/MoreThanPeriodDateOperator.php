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
     * The first matching second: right after the boundary for a fixed period, the boundary itself for a custom
     * formula (so two conditions More than + Less than with formulas cover whole days without a gap).
     * See LessThanPeriodDateOperator::periodBoundary().
     *
     * @param mixed $searchValue
     * @return int|null The UNIX timestamp or null if the search value is invalid.
     */
    public static function firstSecond($searchValue): ?int
    {
        $boundary = LessThanPeriodDateOperator::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return null;
        }

        return LessThanPeriodDateOperator::isCustom($searchValue) ? $boundary : $boundary + 1;
    }

    /**
     * See LessThanPeriodDateOperator::validateValue().
     *
     * @param mixed $searchValue
     * @return string|null
     */
    public static function validateValue($searchValue): ?string
    {
        return LessThanPeriodDateOperator::validateValue($searchValue);
    }

    /**
     * Checks if the value is a date after now + quantity * period (from the boundary on for a custom formula).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric|string]
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $firstSecond = self::firstSecond($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($firstSecond) || is_null($valueTimestamp)) {
            return false;
        }

        return $valueTimestamp >= $firstSecond;
    }

    /**
     * Generate a condition array for MongoDB (whole-second precision like PHP).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric|string]
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $firstSecond = self::firstSecond($searchValue);

        if (is_null($firstSecond)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$gte' => self::convertToMongoDate($firstSecond)]];
    }

    /**
     * Generate a condition array for Postgres (whole-second precision like PHP).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric|string]
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $firstSecond = self::firstSecond($searchValue);

        if (is_null($firstSecond)) {
            return self::postgresqlMatchNothing();
        }

        return ['>=', $column, self::postgresqlTimestamp($firstSecond)];
    }

    public static function convertPeriodToSeconds(string $period, int $quantity = 1)
    {
        return LessThanPeriodDateOperator::convertPeriodToSeconds($period, $quantity);
    }

}
