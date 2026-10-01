<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class MoreThanDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'more_than_date_operator';
    }

    public static function name(): string
    {
        return 'More than';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'More than';
    }

    /**
     * Checks if the value is a date after the search date.
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

        return $valueTimestamp > $searchTimestamp;
    }

    /**
     * Generate a condition array for MongoDB. "After the search second" with whole-second precision, like PHP:
     * a stored 12:00:00.400 is still the second 12:00:00, so it does not match "more than 12:00:00".
     *
     * @param string $column The column name.
     * @param mixed $searchValue A timestamp or any string strtotime() understands.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        $searchTimestamp = self::convertToSearchTimestamp($searchValue);

        if (is_null($searchTimestamp)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$gte' => self::convertToMongoDate($searchTimestamp + 1)]];
    }

    /**
     * Generate a condition array for Postgres (whole-second precision, see mongodbConditions()).
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

        return ['>=', $column, self::postgresqlTimestamp($searchTimestamp + 1)];
    }

}
