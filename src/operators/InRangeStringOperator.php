<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class InRangeStringOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'in_range_string_operator';
    }

    public static function name(): string
    {
        return 'In range';
    }

    public static function group(): string
    {
        return parent::GROUP_STRING;
    }

    public static function description(): string
    {
        return 'In range';
    }

    /**
     * Checks if the value equals one of the texts in the list (non-scalar items are ignored).
     * An empty value is read as ''.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The list of texts.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $value = self::convertToString(self::getValue($column, $data));

        if (is_null($value)) {
            return false;
        }

        return in_array($value, self::_preparedSearchValue($searchValue), true);
    }

    /**
     * Generate a condition array for MongoDB. If '' is in the list, empty (null / missing) values match too.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of texts.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        // Invalid UTF-8 items can not equal any stored value (and MongoDB would reject them with an error)
        $strings = array_values(array_filter(self::_preparedSearchValue($searchValue), [self::class, 'isUtf8']));

        if (empty($strings)) {
            return self::mongodbMatchNothing($column);
        }

        if (in_array('', $strings, true)) {
            $strings[] = null;
        }

        return [$column => ['$in' => $strings]];
    }

    /**
     * Generate a condition array for Postgres. If '' is in the list, NULL matches too.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of texts.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $strings = array_values(array_filter(self::_preparedSearchValue($searchValue), [self::class, 'isUtf8']));

        if (empty($strings)) {
            return self::postgresqlMatchNothing();
        }

        if (in_array('', $strings, true)) {
            return ['or', ['in', $column, $strings], [$column => null]];
        }

        return ['in', $column, $strings];
    }

    /**
     * Scalar items of the list cast to strings, other items are skipped.
     *
     * @param mixed $searchValue
     * @return array
     */
    private static function _preparedSearchValue($searchValue): array
    {
        if (!is_array($searchValue)) {
            return [];
        }

        $strings = [];

        foreach ($searchValue as $item) {
            if (is_scalar($item)) {
                $strings[] = strval($item);
            }
        }

        return $strings;
    }

}
