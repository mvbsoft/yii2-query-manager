<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class NotInRangeIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'not_in_range_int_operator';
    }

    public static function name(): string
    {
        return 'Not in range';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Not in range';
    }

    /**
     * Checks if the value is a number that is not in the list (non-numeric items are ignored).
     * An empty (null / missing) value never matches; a search value that is not a list matches nothing.
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The list of numbers.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $valueNumber = self::convertToNumber(self::getValue($column, $data));

        if (!is_array($searchValue) || is_null($valueNumber)) {
            return false;
        }

        foreach (self::_preparedSearchValue($searchValue) as $number) {
            if ($valueNumber == $number) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a condition array for MongoDB. null in `$nin` also excludes documents without the column.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of numbers.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        if (!is_array($searchValue)) {
            return self::mongodbMatchNothing($column);
        }

        $numbers = self::_preparedSearchValue($searchValue);
        $numbers[] = null;

        return [$column => ['$nin' => $numbers]];
    }

    /**
     * Generate a condition array for Postgres (NULL never matches "NOT IN").
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of numbers.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        if (!is_array($searchValue)) {
            return self::postgresqlMatchNothing();
        }

        $numbers = self::_preparedSearchValue($searchValue);

        if (empty($numbers)) {
            return ['not', [$column => null]];
        }

        return ['not in', $column, array_map([self::class, 'postgresqlNumber'], $numbers)];
    }

    /**
     * Numeric items of the list (as int|float), other items are skipped.
     *
     * @param array $searchValue
     * @return array
     */
    private static function _preparedSearchValue(array $searchValue): array
    {
        $numbers = [];

        foreach ($searchValue as $item) {
            $number = self::convertToNumber($item);

            if (!is_null($number)) {
                $numbers[] = $number;
            }
        }

        return $numbers;
    }

}
