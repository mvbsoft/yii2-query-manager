<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_date_operator';
    }

    public static function name(): string
    {
        return 'Not equal';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Not equal';
    }

    /**
     * Checks if the value is a date outside the calendar day (PHP default timezone) of the search date.
     * An empty value never matches.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue A timestamp or any string strtotime() understands.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $searchTimestamp = self::convertToSearchTimestamp($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (is_null($searchTimestamp) || is_null($valueTimestamp)) {
            return false;
        }

        $day = self::dayRange($searchTimestamp);

        return $valueTimestamp < $day['from'] || $valueTimestamp > $day['to'];
    }

    /**
     * Generate a condition array for MongoDB: before or after the whole day of the search date
     * (with whole-second precision, see mongodbDateRange()).
     *
     * @param string $column The column name.
     * @param mixed $searchValue A timestamp or any string strtotime() understands.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $searchTimestamp = self::convertToSearchTimestamp($searchValue);

        if (is_null($searchTimestamp)) {
            return self::mongodbMatchNothing($column);
        }

        $day = self::dayRange($searchTimestamp);

        return [
            '$or' => [
                [$column => ['$lt' => self::convertToMongoDate($day['from'])]],
                [$column => ['$gte' => self::convertToMongoDate($day['to'] + 1)]]
            ]
        ];
    }

    /**
     * Generate a condition array for Postgres: before or after the whole day of the search date.
     *
     * @param string $column The column name.
     * @param mixed $searchValue A timestamp or any string strtotime() understands.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $searchTimestamp = self::convertToSearchTimestamp($searchValue);

        if (is_null($searchTimestamp)) {
            return self::postgresqlMatchNothing();
        }

        $day = self::dayRange($searchTimestamp);

        return [
            'or',
            ['<', $column, self::postgresqlTimestamp($day['from'])],
            ['>=', $column, self::postgresqlTimestamp($day['to'] + 1)]
        ];
    }

}
