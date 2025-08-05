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
     * Checks if the given value is less than the search value after converting them to timestamps.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is less than the search value after converting them to timestamps, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        if(
            !array_key_exists('period', $searchValue) ||
            !in_array($searchValue['period'], self::getPeriods()) ||
            !array_key_exists('quantity', $searchValue) ||
            !is_numeric($searchValue['quantity'])
        ){
            return false;
        }

        $periodInSeconds = self::convertPeriodToSeconds($searchValue['period'], $searchValue['quantity']);

        return Carbon::now()->addSeconds($periodInSeconds)->timestamp > Carbon::parse($value)->timestamp;
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
        return [];
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
