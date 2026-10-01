<?php

namespace mvbsoft\queryManager\operators;

use MongoDB\BSON\Regex;
use mvbsoft\queryManager\OperatorAbstract;

class StartWithStringOperator extends OperatorAbstract
{
    public static function slug(): string
    {
        return 'start_with_string_operator';
    }

    public static function name(): string
    {
        return 'Start with';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Start with';
    }

    /**
     * Checks (case-insensitive) if the value starts with the search text. An empty value is read as ''.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The prefix.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (!is_scalar($searchValue) || is_null($value)) {
            return false;
        }

        return self::phpLiteralMatch(strval($searchValue), $value, '^');
    }

    /**
     * Constructs a condition for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The prefix.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_scalar($searchValue) || !self::isUtf8(strval($searchValue))) {
            return self::mongodbMatchNothing($column);
        }

        $searchValue = strval($searchValue);

        // Every value (an empty one too) starts with an empty string
        if ($searchValue === '') {
            return self::mongodbMatchAll($column);
        }

        return [$column => new Regex('^' . preg_quote($searchValue, '/'), 'i')];
    }

    /**
     * Constructs a condition for PostgreSQL (ILIKE 'text%' with % and _ in the text escaped).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The prefix.
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

        return ['ILIKE', $column, self::postgresqlLikeEscape($searchValue) . '%', false];
    }
}
