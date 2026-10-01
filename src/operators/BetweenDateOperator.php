<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class BetweenDateOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'between_date_operator';
    }

    public static function name(): string
    {
        return 'Between';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Between Date';
    }

    /**
     * Checks if the value is a date within the range (bounds included).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['from' => date, 'to' => date] or [date, date]; dates are timestamps or strtotime() strings.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $range = self::_preparedSearchValue($searchValue);
        $valueTimestamp = self::convertToTimestamp(self::getValue($column, $data));

        if (empty($range) || is_null($valueTimestamp)) {
            return false;
        }

        return $valueTimestamp >= $range['from'] && $valueTimestamp <= $range['to'];
    }

    /**
     * Constructs a condition for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => date, 'to' => date] or [date, date].
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::mongodbMatchNothing($column);
        }

        return self::mongodbDateRange($column, $range['from'], $range['to']);
    }

    /**
     * Constructs a condition for PostgreSQL.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => date, 'to' => date] or [date, date].
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::postgresqlMatchNothing();
        }

        return self::postgresqlDateRange($column, $range['from'], $range['to']);
    }

    /**
     * Normalizes the range: accepts ['from' => x, 'to' => y] or [x, y] and converts both bounds to timestamps.
     *
     * @param mixed $searchValue
     * @return array ['from' => int, 'to' => int] or an empty array if the range is invalid.
     */
    private static function _preparedSearchValue($searchValue): array
    {
        if (!is_array($searchValue)) {
            return [];
        }

        if (array_key_exists('from', $searchValue) && array_key_exists('to', $searchValue)) {
            $from = $searchValue['from'];
            $to = $searchValue['to'];
        } elseif (array_key_exists(0, $searchValue) && array_key_exists(1, $searchValue)) {
            $from = $searchValue[0];
            $to = $searchValue[1];
        } else {
            return [];
        }

        $from = self::convertToSearchTimestamp($from);
        $to = self::convertToSearchTimestamp($to);

        if (is_null($from) || is_null($to)) {
            return [];
        }

        return [
            'from' => $from,
            'to'   => $to
        ];
    }

}
