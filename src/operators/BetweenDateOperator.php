<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

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
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue An array containing two elements representing the start and end timestamps of the range.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the timestamp falls within the specified range, false otherwise.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $searchValue is an array with exactly two elements
        if(!is_array($searchValue) || count($searchValue) !== 2){
            return false;
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

    public static function mongodbConditions($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Constructs a condition for PostgreSQL database query to find records with a value in the specified column between two timestamps.
     *
     * @param mixed $searchValue An array containing two timestamps to define the range.
     * @param string $column The column name in the database.
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        // Check if $searchValue is an array with exactly two elements
        if(!is_array($searchValue) || count($searchValue) !== 2){
            return [];
        }

        // Convert start and end timestamps to Unix timestamp format
        $fromDate = self::convertToTimestamp($searchValue[0]);
        $toDate   = self::convertToTimestamp($searchValue[1]);

        // Construct the condition for between two timestamps
        return ['between', $column, new Expression("to_timestamp($fromDate)"), new Expression("to_timestamp($toDate)")];
    }

}
