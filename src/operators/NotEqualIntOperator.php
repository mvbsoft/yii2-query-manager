<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_int_operator';
    }

    public static function name(): string
    {
        return 'Not equal';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Not equal';
    }

    /**
     * Checks if the value is a number that is not equal to the search value.
     * An empty (null / missing) value never matches.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The value to be checked.
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

        return $valueNumber != $searchNumber;
    }

    /**
     * Generate a condition array for MongoDB. `$nin` with null also excludes documents without the column,
     * the same way phpConditions() rejects an empty value.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The numeric value to exclude.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$nin' => [$searchNumber, null]]];
    }

    /**
     * Generate a condition array for Postgres (NULL never matches "!=").
     *
     * @param string $column The column name.
     * @param mixed $searchValue The numeric value to exclude.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::postgresqlMatchNothing();
        }

        return ['!=', $column, self::postgresqlNumber($searchNumber)];
    }

}
