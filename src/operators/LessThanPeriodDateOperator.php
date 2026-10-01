<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;

class LessThanPeriodDateOperator extends OperatorAbstract
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
     * The boundary moment: now + quantity * period (a negative quantity points to the past,
     * e.g. ['period' => 'days', 'quantity' => -15] is "15 days ago"). Months are 30 days, years are 365 days.
     *
     * The quantity must be a whole number (an int or a numeric string such as "-15"); a fractional quantity or a
     * boundary outside years 0001..9999 makes the search value invalid.
     *
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return int|null The UNIX timestamp or null if the search value is invalid.
     */
    public static function periodBoundary($searchValue): ?int
    {
        if (
            !is_array($searchValue) ||
            !in_array($searchValue['period'] ?? null, self::getPeriods(), true) ||
            !is_numeric($searchValue['quantity'] ?? null)
        ) {
            return null;
        }

        $quantity = $searchValue['quantity'] + 0;

        if (!is_finite($quantity) || floor($quantity) != $quantity) {
            return null;
        }

        // Float arithmetic: a huge quantity must not overflow the integer range
        $boundary = Carbon::now()->timestamp + (float)self::convertPeriodToSeconds($searchValue['period']) * $quantity;

        if (!self::isTimestampInRange($boundary)) {
            return null;
        }

        return (int)$boundary;
    }

    /**
     * Checks if the value is a date before now + quantity * period.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $boundary = self::periodBoundary($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($boundary) || is_null($valueTimestamp)) {
            return false;
        }

        return $valueTimestamp < $boundary;
    }

    /**
     * Generate a condition array for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $boundary = self::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$lt' => self::convertToMongoDate($boundary)]];
    }

    /**
     * Generate a condition array for Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['period' => string, 'quantity' => numeric]
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $boundary = self::periodBoundary($searchValue);

        if (is_null($boundary)) {
            return self::postgresqlMatchNothing();
        }

        return ['<', $column, self::postgresqlTimestamp($boundary)];
    }

    public static function convertPeriodToSeconds(string $period, int $quantity = 1)
    {
        switch (strtolower($period)) {
            case self::PERIOD_YEARS:
                $secondsInPeriod = 31536000;
                break;
            case self::PERIOD_MONTHS:
                $secondsInPeriod = 2592000;
                break;
            case self::PERIOD_WEEKS:
                $secondsInPeriod = 604800;
                break;
            case self::PERIOD_DAYS:
                $secondsInPeriod = 86400;
                break;
            case self::PERIOD_HOURS:
                $secondsInPeriod = 3600;
                break;
            case self::PERIOD_MINUTES:
                $secondsInPeriod = 60;
                break;
            default:
                $secondsInPeriod = 1;
        }

        return $secondsInPeriod * $quantity;
    }

}
