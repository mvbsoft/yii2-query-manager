<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class LessThanIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_int_operator';
    }

    public static function name(): string
    {
        return 'Less than';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Less than';
    }

    /**
     * Checks if the value is a number less than the search value.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The value to compare with.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $searchNumber = self::convertToNumber($searchValue);
        $valueNumber = self::convertToNumber(self::getValue($column, $data));

        if (is_null($searchNumber) || is_null($valueNumber)) {
            return false;
        }

        return $valueNumber < $searchNumber;
    }

    /**
     * Generate a condition array for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to compare with.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$lt' => $searchNumber]];
    }

    /**
     * Generate a condition array for Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to compare with.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::postgresqlMatchNothing();
        }

        return ['<', $column, self::postgresqlNumber($searchNumber)];
    }

}
