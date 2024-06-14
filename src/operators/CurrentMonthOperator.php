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
     * Checks if the given value represents the current month.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value represents the current month, otherwise returns false.
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

        // Check if the value represents the current month
        return Carbon::createFromTimestamp($value)->isCurrentMonth();
    }

    /**
     * Generate a condition array for MongoDB to match the current month.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        // Calculate the start and end timestamps of the current month
        $start = Carbon::now()->startOfMonth()->timestamp;
        $end = Carbon::now()->endOfMonth()->timestamp;

        // Construct the condition for MongoDB
        return [
            $column => [
                '$gte' => self::convertToMongoDate($start),
                '$lte' => self::convertToMongoDate($end),
            ],
        ];
    }

    /**
     * Generate a condition array for the query builder to match the current month.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue = null): array
    {
        return [
            'and',
            [new Expression("date_part('year', $column) = date_part('year', current_date)")],
            [new Expression("date_part('month', $column) = date_part('month', current_date)")]
        ];
    }
}
