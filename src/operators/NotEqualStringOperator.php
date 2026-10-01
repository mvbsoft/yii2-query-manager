<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotEqualStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_equal_string_operator';
    }

    public static function name(): string
    {
        return 'Not equal string';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not equal string';
    }

    /**
     * Checks if the value differs from the search text (case-sensitive). An empty value is read as ''.
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

        return strval($searchValue) !== $value;
    }

    /**
     * Generate a condition array for MongoDB (`$ne` also matches documents without the column,
     * which are read as '').
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to compare with.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue)) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        // Invalid UTF-8 can not be stored, so every value differs from it (MongoDB would reject it with an error)
        if (!self::isUtf8($searchValue)) {
            return self::mongodbMatchAll($column);
        }

        if ($searchValue === '') {
            return [$column => ['$nin' => ['', null]]];
        }

        return [$column => ['$ne' => $searchValue]];
    }

    /**
     * Generate a condition array for Postgres. NULL is read as '' — so it matches any non-empty search text.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to compare with.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue)) {
            return self::postgresqlMatchNothing();
        }

        $searchValue = strval($searchValue);

        if (!self::isUtf8($searchValue)) {
            return self::postgresqlMatchAll();
        }

        if ($searchValue === '') {
            return ['!=', $column, ''];
        }

        return ['or', ['!=', $column, $searchValue], [$column => null]];
    }

}
