<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class BetweenIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'between_int_operator';
    }

    public static function name(): string
    {
        return 'Between';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'Between Integer';
    }

    /**
     * Checks if a given integer falls within a specified range.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing two elements representing the start and end of the range.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the integer falls within the specified range, false otherwise.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        if(empty($preparedSearchValue)){
            return false;
        }

        // Check if all values are numeric
        if (!is_numeric($value)) {
            return false;
        }

        $value = intval($value);

        // Check if $value is between $fromInt and $toInt
        return $value >= $preparedSearchValue['from'] && $value <= $preparedSearchValue['to'];
    }

    /**
     * Constructs a condition for MongoDB database query to find records with a value in the specified column between two integers.
     *
     * @param string $column The column name in the database.
     * @param mixed $searchValue An array containing two elements representing the start and end of the range.
     * @return array The condition array for the query.
     */
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        if(empty($preparedSearchValue)){
            return [];
        }

        // Construct the condition for MongoDB
        return [
            $column => [
                '$gte' => $preparedSearchValue['from'],
                '$lte' => $preparedSearchValue['to']
            ]
        ];
    }

    /**
     * Constructs a condition for PostgreSQL database query to find records with a value шn the specified column between two integers.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing two elements representing the start and end of the range.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        if(empty($preparedSearchValue)){
            return [];
        }

        // Construct the condition for between two timestamps
        return ['between', $column, $preparedSearchValue['from'], $preparedSearchValue['to']];
    }

    private static function _preparedSearchValue($searchValue): array
    {
        // Check if $searchValue is an array and has exactly two elements
        if(
            !is_array($searchValue) ||
            !array_key_exists('from', $searchValue)||
            !array_key_exists('to', $searchValue))
        {
            return [];
        }

        // Get the start and end values of the range
        $from = $searchValue['from'];
        $to = $searchValue['to'];

        // Check if all values are numeric
        if (!is_numeric($from) || !is_numeric($to)) {
            return [];
        }

        // Convert values to integers
        $fromInt = intval($from);
        $toInt = intval($to);

        return [
            'from' => $fromInt,
            'to' => $toInt
        ];
    }

}
