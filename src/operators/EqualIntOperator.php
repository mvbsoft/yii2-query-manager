<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class EqualIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'equal_int_operator';
    }

    public static function name(): string
    {
        return 'Equal';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Equal';
    }

    /**
     * Checks if two given values are equal numerically (no truncation: 5.7 is not equal to 5).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The value to be checked.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if both values are equal numerically, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $searchNumber = self::convertToNumber($searchValue);
        $valueNumber = self::convertToNumber(self::getValue($column, $data));

        if (is_null($searchNumber) || is_null($valueNumber)) {
            return false;
        }

        return $valueNumber == $searchNumber;
    }

    /**
     * Generate a condition array for MongoDB to match a numeric value.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The numeric value to search for.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$eq' => $searchNumber]];
    }

    /**
     * Generate a condition array for the query builder to match a numeric value in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The numeric value to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $searchNumber = self::convertToNumber($searchValue);

        if (is_null($searchNumber)) {
            return self::postgresqlMatchNothing();
        }

        return ['=', $column, self::postgresqlNumber($searchNumber)];
    }

}
