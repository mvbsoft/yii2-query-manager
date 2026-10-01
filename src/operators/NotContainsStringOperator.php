<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
use mvbsoft\queryManager\OperatorAbstract;

class NotContainsStringOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'not_contains_string_operator';
    }

    public static function name(): string
    {
        return 'Not contains';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not contains';
    }

    /**
     * Checks (case-insensitive) if the value does not contain the search text.
     * An empty value is read as '' — so it matches any non-empty search text.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The text that must be absent.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (!is_scalar($searchValue) || is_null($value)) {
            return false;
        }

        return !self::phpLiteralMatch(strval($searchValue), $value);
    }

    /**
     * Constructs a condition for MongoDB (`$not` also matches documents without the column).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text that must be absent.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue)) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        // Invalid UTF-8 can not be part of a stored text, so no value contains it (MongoDB would reject it)
        if (!self::isUtf8($searchValue)) {
            return self::mongodbMatchAll($column);
        }

        // Every value contains an empty string
        if ($searchValue === '') {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$not' => new Regex(preg_quote($searchValue, '/'), 'i')]];
    }

    /**
     * Constructs a condition for PostgreSQL. NULL is added explicitly: NOT ILIKE never matches NULL.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text that must be absent.
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
            return self::postgresqlMatchNothing();
        }

        return ['or', ['NOT ILIKE', $column, $searchValue], [$column => null]];
    }
}
