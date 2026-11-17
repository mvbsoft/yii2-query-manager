<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class DaysOfWeekOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'days_of_week_operator';
    }

    public static function name(): string
    {
        return 'Days Of Week';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Date filtering based on the days of the week';
    }

    /**
     * Checks if the value is a date that falls on one of the given weekdays (0 = Sunday ... 6 = Saturday),
     * in the PHP default timezone. An empty value never matches.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The list of weekdays 0..6.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $days = self::_preparedSearchValue($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (empty($days) || is_null($valueTimestamp) || !self::isTimestampInRange($valueTimestamp)) {
            return false;
        }

        return in_array(Carbon::createFromTimestamp($valueTimestamp)->dayOfWeek, $days, true);
    }

    /**
     * Constructs a condition for MongoDB. `$dayOfWeek` is 1 = Sunday ... 7 = Saturday and is evaluated in the
     * PHP default timezone; values that are not BSON dates never match (instead of failing the whole query).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of weekdays 0..6.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $days = self::_preparedSearchValue($searchValue);

        if (empty($days)) {
            return self::mongodbMatchNothing($column);
        }

        $mongoDays = array_map(function ($day) {
            return $day + 1;
        }, $days);

        return [
            '$expr' => [
                '$and' => array_merge(self::mongodbExprIsDate($column), [
                    ['$in' => [
                        ['$dayOfWeek' => ['date' => '$' . $column, 'timezone' => date_default_timezone_get()]],
                        $mongoDays
                    ]]
                ])
            ]
        ];
    }

    /**
     * Constructs a condition for PostgreSQL (EXTRACT(DOW) is 0 = Sunday ... 6 = Saturday, session timezone).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of weekdays 0..6.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        $days = self::_preparedSearchValue($searchValue);

        if (empty($days)) {
            return self::postgresqlMatchNothing();
        }

        return ['and', self::postgresqlDateInRange($column), new Expression("EXTRACT(DOW FROM [[$column]]) IN (" . implode(', ', $days) . ")")];
    }

    /**
     * Validates the weekdays: every item must read as "0".."6" (an int, or a string / float with exactly that
     * text — the same rule as before: "4" and 4.0 are valid, "04", " 4", true are not).
     *
     * @param mixed $searchValue
     * @return int[] The weekdays or an empty array if the list is invalid.
     */
    private static function _preparedSearchValue($searchValue): array
    {
        if (!is_array($searchValue)) {
            return [];
        }

        $days = [];

        foreach ($searchValue as $day) {
            if (is_bool($day) || !is_scalar($day) || !in_array((string)$day, ['0', '1', '2', '3', '4', '5', '6'], true)) {
                return [];
            }

            $days[] = intval($day);
        }

        return array_values(array_unique($days));
    }
}
