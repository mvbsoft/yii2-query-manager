<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;

class BetweenDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'between_date_operator';
    }

    public static function name(): string
    {
        return 'Between';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Between';
    }

    /**
     * Checks if a given timestamp falls within a specified range.
     *
     * @param mixed $searchValue An array containing two elements representing the start and end timestamps of the range.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the timestamp falls within the specified range, false otherwise.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $searchValue is an array with exactly two elements
        if(!is_array($searchValue) || count($searchValue) !== 2){
            return false; // If not, return false
        }

        // Convert start and end timestamps to Unix timestamp format
        $fromDate = self::convertToTimestamp($searchValue[0]);
        $toDate   = self::convertToTimestamp($searchValue[1]);
        $value    = self::convertToTimestamp($value);

        // Check if any of the timestamps couldn't be converted
        if(is_null($fromDate) || is_null($toDate) || is_null($value)){
            return false; // If any timestamp is invalid, return false
        }

        // Check if the value falls within the specified range
        return $value >= $fromDate && $value <= $toDate;
    }

    public static function mongodbCondition($searchValue, $column) : array
    {
        return [];
    }

    public static function postgresqlCondition($searchValue, $column) : array
    {
        return [];
    }

}
