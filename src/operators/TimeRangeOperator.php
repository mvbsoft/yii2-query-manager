<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class TimeRangeOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'time_range_operator';
    }

    public static function name(): string
    {
        return 'Time Range';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Date filtering by hour and minute in range';
    }

    /**
     * Checks if the time of day of the value (seconds since midnight, wall clock in the PHP default timezone)
     * is within [from, to]. If from > to the range wraps over midnight (e.g. 22:00 - 06:00). Any date matches,
     * not only today's.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['from' => int seconds, 'to' => int seconds].
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $range = self::_preparedSearchValue($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (empty($range) || is_null($valueTimestamp) || !self::isTimestampInRange($valueTimestamp)) {
            return false;
        }

        $seconds = self::secondsOfDay($valueTimestamp);

        if ($range['from'] <= $range['to']) {
            return $seconds >= $range['from'] && $seconds <= $range['to'];
        }

        return $seconds >= $range['from'] || $seconds <= $range['to'];
    }

    /**
     * Constructs a condition for MongoDB. The time of day is evaluated in the PHP default timezone;
     * values that are not BSON dates never match (instead of failing the whole query).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => int seconds, 'to' => int seconds].
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::mongodbMatchNothing($column);
        }

        $date = ['date' => '$' . $column, 'timezone' => date_default_timezone_get()];

        $seconds = [
            '$add' => [
                ['$multiply' => [['$hour' => $date], 3600]],
                ['$multiply' => [['$minute' => $date], 60]],
                ['$second' => $date]
            ]
        ];

        $inRange = $range['from'] <= $range['to'] ? '$and' : '$or';

        return [
            '$expr' => [
                '$and' => array_merge(self::mongodbExprIsDate($column), [
                    [$inRange => [
                        ['$gte' => [$seconds, $range['from']]],
                        ['$lte' => [$seconds, $range['to']]]
                    ]]
                ])
            ]
        ];
    }

    /**
     * Constructs a condition for PostgreSQL (wall-clock time of day in the session timezone).
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => int seconds, 'to' => int seconds].
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::postgresqlMatchNothing();
        }

        $seconds = "(EXTRACT(HOUR FROM [[$column]]) * 3600 + EXTRACT(MINUTE FROM [[$column]]) * 60 + FLOOR(EXTRACT(SECOND FROM [[$column]])))";

        $inRange = $range['from'] <= $range['to'] ? 'AND' : 'OR';

        return ['and', self::postgresqlDateInRange($column), new Expression("($seconds >= {$range['from']} $inRange $seconds <= {$range['to']})")];
    }

    /**
     * Validates the range: 'from' and 'to' must be integers (the same rule as before).
     *
     * @param mixed $searchValue
     * @return array ['from' => int, 'to' => int] or an empty array if the range is invalid.
     */
    private static function _preparedSearchValue($searchValue): array
    {
        if(
            !is_array($searchValue) ||
            !array_key_exists('from', $searchValue) ||
            !array_key_exists('to', $searchValue) ||
            !is_int($searchValue['from']) ||
            !is_int($searchValue['to'])
        ){
            return [];
        }

        return [
            'from' => $searchValue['from'],
            'to'   => $searchValue['to']
        ];
    }
}
