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
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        if(empty($preparedSearchValue)){
            return false;
        }

        // Get value from array
        $value = self::getValue($column, $data);

        $valueTimestamp = self::convertToTimestamp($value);

        // Check if any of the timestamps couldn't be converted
        if(is_null($valueTimestamp)){
            return false; // If any timestamp is invalid, return false
        }

        // Convert start and end timestamps to Unix timestamp format
        $fromDate = $preparedSearchValue['from'];
        $toDate   = $preparedSearchValue['to'];

        // Check if the value falls within the specified range
        return $valueTimestamp >= $fromDate && $valueTimestamp <= $toDate;
    }

    /**
     * Constructs a condition for MongoDB database query to find records with a value in the specified column between two timestamps.
     *
     * @param string $column The column name in the database.
     * @param mixed $searchValue An array containing two elements representing the start and end timestamps of the range.
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
                '$gte' => self::convertToMongoDate($preparedSearchValue['from']),
                '$lte' => self::convertToMongoDate($preparedSearchValue['to'])
            ]
        ];
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
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        if(empty($preparedSearchValue)){
            return [];
        }

        // Convert start and end timestamps to Unix timestamp format
        $fromDate = $preparedSearchValue['from'];
        $toDate   = $preparedSearchValue['to'];

        // Construct the condition for between two timestamps
        return ['between', $column, new Expression("to_timestamp($fromDate)"), new Expression("to_timestamp($toDate)")];
    }

    private static function _preparedSearchValue($searchValue): array
    {
        // Check if $searchValue is an array with exactly two elements
        if(
            !is_array($searchValue) ||
            !array_key_exists('from', $searchValue)||
            !array_key_exists('to', $searchValue))
        {
            return [];
        }

        $fromDate = self::convertToTimestamp($searchValue['from']);
        $toDate   = self::convertToTimestamp($searchValue['to']);

        // Check if any of the timestamps couldn't be converted
        if(is_null($fromDate) || is_null($toDate)){
            return []; // If any timestamp is invalid, return an empty array
        }

        $fromDate = Carbon::createFromTimestamp($fromDate)->startOfDay()->timestamp;
        $toDate = Carbon::createFromTimestamp($toDate)->endOfDay()->timestamp;

        // Convert start and end timestamps to Unix timestamp format and return
        return [
            'from' => $fromDate,
            'to'   => $toDate
        ];
    }

}
