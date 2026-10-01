<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class LessThanDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_date_operator';
    }

    public static function name(): string
    {
        return 'Less than date';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Less than date';
    }

    /**
     * Checks if the value is a date before the search date.
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

        return $valueTimestamp < $searchTimestamp;
    }

    /**
     * Generate a condition array for MongoDB.
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

        return [$column => ['$lt' => self::convertToMongoDate($searchTimestamp)]];
    }

    /**
     * Generate a condition array for Postgres.
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

        return ['<', $column, self::postgresqlTimestamp($searchTimestamp)];
    }

}
