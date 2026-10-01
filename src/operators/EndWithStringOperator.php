<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
use mvbsoft\queryManager\OperatorAbstract;

class EndWithStringOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'end_with_string_operator';
    }

    public static function name(): string
    {
        return 'End with';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'End with';
    }

    /**
     * Checks (case-insensitive) if the value ends with the search text. An empty value is read as ''.
     * `\z` (not `$`) is the anchor: `$` would also match before a trailing newline, unlike PostgreSQL LIKE.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The suffix.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (!is_scalar($searchValue) || is_null($value)) {
            return false;
        }

        return self::phpLiteralMatch(strval($searchValue), $value, '', '\z');
    }

    /**
     * Constructs a condition for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The suffix.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        // Every value (an empty one too) ends with an empty string
        if ($searchValue === '') {
            return self::mongodbMatchAll($column);
        }

        return [$column => new Regex(preg_quote($searchValue, '/') . '\z', 'i')];
    }

    /**
     * Constructs a condition for PostgreSQL (ILIKE '%text' with % and _ in the text escaped).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The suffix.
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

        return ['ILIKE', $column, '%' . self::postgresqlLikeEscape($searchValue), false];
    }
}
