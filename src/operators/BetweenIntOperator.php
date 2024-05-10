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
        return 'Contains';
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

        // Check if $searchValue is an array and has exactly two elements
        if (!is_array($searchValue) || count($searchValue) !== 2) {
            return false; // If not, return false
        }

        // Get the start and end values of the range
        $from = $searchValue[0];
        $to = $searchValue[1];

        // Check if all values are numeric
        if (!is_numeric($from) || !is_numeric($to) || !is_numeric($value)) {
            return false;
        }

        // Convert values to integers
        $fromInt = intval($from);
        $toInt = intval($to);
        $value = intval($value);

        // Check if $value is between $fromInt and $toInt
        return $value >= $fromInt && $value <= $toInt;
    }

    public static function mongodbConditions(string $column, $searchValue) : array
    {
        return [];
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
        // Check if $searchValue is an array and has exactly two elements
        if (!is_array($searchValue) || count($searchValue) !== 2) {
            return []; // If not, return false
        }

        // Get the start and end values of the range
        $from = $searchValue[0];
        $to = $searchValue[1];

        // Check if all values are numeric
        if (!is_numeric($from) || !is_numeric($to) ) {
            return [];
        }

        // Convert values to integers
        $fromInt = intval($from);
        $toInt = intval($to);

        // Construct the condition for between two timestamps
        return ['between', $column, $fromInt, $toInt];
    }

}
