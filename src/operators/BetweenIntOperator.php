<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class BetweenIntOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'between_int_operator';
    }

    public static function name(): string
    {
        return 'Between';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Between Integer';
    }

    /**
     * Checks if a number falls within the specified range (bounds included).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue ['from' => x, 'to' => y] or [x, y].
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $range = self::_preparedSearchValue($searchValue);
        $valueNumber = self::convertToNumber(self::getValue($column, $data));

        if (empty($range) || is_null($valueNumber)) {
            return false;
        }

        return $valueNumber >= $range['from'] && $valueNumber <= $range['to'];
    }

    /**
     * Constructs a condition for MongoDB to find documents with a number in the range.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => x, 'to' => y] or [x, y].
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::mongodbMatchNothing($column);
        }

        return [
            $column => [
                '$gte' => $range['from'],
                '$lte' => $range['to']
            ]
        ];
    }

    /**
     * Constructs a condition for PostgreSQL to find rows with a number in the range.
     *
     * @param string $column The column name.
     * @param mixed $searchValue ['from' => x, 'to' => y] or [x, y].
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        $range = self::_preparedSearchValue($searchValue);

        if (empty($range)) {
            return self::postgresqlMatchNothing();
        }

        return ['between', $column, self::postgresqlNumber($range['from']), self::postgresqlNumber($range['to'])];
    }

    /**
     * Normalizes the range: accepts ['from' => x, 'to' => y] or [x, y] with numeric bounds.
     *
     * @param mixed $searchValue
     * @return array ['from' => int|float, 'to' => int|float] or an empty array if the range is invalid.
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

        $from = self::convertToNumber($from);
        $to = self::convertToNumber($to);

        if (is_null($from) || is_null($to)) {
            return [];
        }

        return [
            'from' => $from,
            'to' => $to
        ];
    }
}
