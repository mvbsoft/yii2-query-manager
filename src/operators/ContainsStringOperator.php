<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
use mvbsoft\queryManager\OperatorAbstract;

class ContainsStringOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'contains_string_operator';
    }

    public static function name(): string
    {
        return 'Contains';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Contains String';
    }

    /**
     * Checks (case-insensitive) if the value contains the search text. An empty value is read as ''.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The text to search for.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (!is_scalar($searchValue) || is_null($value)) {
            return false;
        }

        return self::phpLiteralMatch(strval($searchValue), $value);
    }

    /**
     * Constructs a condition for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to search for.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        // Every value (an empty one too) contains an empty string
        if ($searchValue === '') {
            return self::mongodbMatchAll($column);
        }

        return [$column => new Regex(preg_quote($searchValue, '/'), 'i')];
    }

    /**
     * Constructs a condition for PostgreSQL (ILIKE '%text%', wildcards in the text are escaped by Yii).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The text to search for.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::postgresqlMatchNothing();
        }

        $searchValue = strval($searchValue);

        if ($searchValue === '') {
            return self::postgresqlMatchAll();
        }

        return ['ILIKE', $column, $searchValue];
    }
}
