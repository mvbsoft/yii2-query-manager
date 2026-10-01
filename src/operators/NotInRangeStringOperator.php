<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotInRangeStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_in_range_string_operator';
    }

    public static function name(): string
    {
        return 'Not in range';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'Not in range';
    }

    /**
     * Checks if the value is not one of the texts in the list (non-scalar items are ignored).
     * An empty value is read as ''; a search value that is not a list matches nothing.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The list of texts.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        // A non-scalar value (e.g. an array field) can not equal any text, so it is "not in" the list — as before
        if (is_null($value)) {
            return true;
        }

        if (!is_array($searchValue)) {
            return false;
        }

        return !in_array($value, self::_preparedSearchValue($searchValue), true);
    }

    /**
     * Generate a condition array for MongoDB (`$nin` also matches documents without the column, which are
     * read as '' — unless '' itself is in the list).
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of texts.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_array($searchValue)) {
            return self::mongodbMatchNothing($column);
        }

        // Invalid UTF-8 items can not equal any stored value (and MongoDB would reject them with an error)
        $strings = array_values(array_filter(self::_preparedSearchValue($searchValue), [self::class, 'isUtf8']));

        if (in_array('', $strings, true)) {
            $strings[] = null;
        }

        return [$column => ['$nin' => $strings]];
    }

    /**
     * Generate a condition array for Postgres. NULL is read as '': it matches unless '' is in the list.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of texts.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        if (!is_array($searchValue)) {
            return self::postgresqlMatchNothing();
        }

        $strings = array_values(array_filter(self::_preparedSearchValue($searchValue), [self::class, 'isUtf8']));

        if (empty($strings)) {
            return self::postgresqlMatchAll();
        }

        if (in_array('', $strings, true)) {
            return ['not in', $column, $strings];
        }

        return ['or', ['not in', $column, $strings], [$column => null]];
    }

    /**
     * Scalar items of the list cast to strings, other items are skipped.
     *
     * @param array $searchValue
     * @return array
     */
    private static function _preparedSearchValue(array $searchValue): array
    {
        $strings = [];

        foreach ($searchValue as $item) {
            if (is_scalar($item)) {
                $strings[] = strval($item);
            }
        }

        return $strings;
    }

}
