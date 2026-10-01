<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class InRangeIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'in_range_int_operator';
    }

    public static function name(): string
    {
        return 'In range';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'In range';
    }

    /**
     * Checks if the value is a number equal to one of the numbers in the list (non-numeric items are ignored).
     *
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $searchValue The list of numbers.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        $numbers = self::_preparedSearchValue($searchValue);
        $valueNumber = self::convertToNumber(self::getValue($column, $data));

        if (is_null($valueNumber)) {
            return false;
        }

        foreach ($numbers as $number) {
            if ($valueNumber == $number) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a condition array for MongoDB.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of numbers.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue): array
    {
        $numbers = self::_preparedSearchValue($searchValue);

        if (empty($numbers)) {
            return self::mongodbMatchNothing($column);
        }

        return [$column => ['$in' => $numbers]];
    }

    /**
     * Generate a condition array for Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The list of numbers.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        $numbers = self::_preparedSearchValue($searchValue);

        if (empty($numbers)) {
            return self::postgresqlMatchNothing();
        }

        return ['in', $column, array_map([self::class, 'postgresqlNumber'], $numbers)];
    }

    /**
     * Numeric items of the list (as int|float), other items are skipped.
     *
     * @param mixed $searchValue
     * @return array
     */
    private static function _preparedSearchValue($searchValue): array
    {
        if (!is_array($searchValue)) {
            return [];
        }

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
