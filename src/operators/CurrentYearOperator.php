<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class CurrentYearOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'current_year_operator';
    }

    public static function name(): string
    {
        return 'Current year';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Current year';
    }

    /**
     * Checks if the given value represents the current year.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value represents the current year, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is a scalar value (string, number, or boolean)
        if (!is_scalar($value)) {
            return false; // If not, return false
        }

        // Convert $value to a timestamp
        $value = self::convertToTimestamp($value);

        // Check if the conversion was successful
        if (is_null($value)) {
            return false; // If not, return false
        }

        // Check if the value represents the current year
        return Carbon::createFromTimestamp($value)->isCurrentYear();
    }

    /**
     * Generate a condition array for MongoDB to match the current year.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $currentYear = Carbon::now()->year;

        $start = Carbon::create($currentYear, 1, 1, 0, 0, 0)->timestamp;
        $end = Carbon::create($currentYear, 12, 31, 23, 59, 59)->timestamp;

        return [
            $column => [
                '$gte' => new UTCDateTime($start * 1000),
                '$lte' => new UTCDateTime($end * 1000),
            ],
        ];
    }

    /**
     * Generate a condition array for the query builder to match the current year.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        return [
            "AND",
            [ new Expression("date_part('year', $column) = date_part('year', current_date)")]
        ];
    }
}
