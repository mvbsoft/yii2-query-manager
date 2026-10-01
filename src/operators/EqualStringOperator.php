<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class EqualStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'equal_string_operator';
    }

    public static function name(): string
    {
        return 'Equal';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Equal';
    }

    /**
     * Checks if the value equals the search text (case-sensitive). An empty value is read as ''.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The text to compare with.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (!is_scalar($searchValue) || is_null($value)) {
            return false;
        }

        return strval($searchValue) === $value;
    }

    /**
     * Generate a condition array for MongoDB. Searching for '' also finds empty (null / missing) values.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to compare with.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        // Invalid UTF-8 can not be stored, so nothing equals it (MongoDB would reject it with an error)
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        if ($searchValue === '') {
            return [$column => ['$in' => ['', null]]];
        }

        return [$column => ['$eq' => $searchValue]];
    }

    /**
     * Generate a condition array for Postgres. Searching for '' also finds NULL.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to compare with.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::postgresqlMatchNothing();
        }

        $searchValue = strval($searchValue);

        if ($searchValue === '') {
            return ['or', [$column => ''], [$column => null]];
        }

        return [$column => $searchValue];
    }

}
