<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class EqualDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'equal_date_operator';
    }

    public static function name(): string
    {
        return 'Equal';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Equal';
    }

    /**
     * Checks if two given values represent the same day.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The value to be checked.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if both values represent the same day, otherwise returns false.
     */
    public static function phpCondition(string $column, $searchValue, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Convert $searchValue and $value to timestamps
        $searchValue = self::convertToTimestamp($searchValue);
        $value = self::convertToTimestamp($value);

        // Check if either of the timestamps couldn't be converted
        if(is_null($searchValue)  || is_null($value)){
            return false; // If so, return false
        }

        // Create Carbon objects from the timestamps
        $searchValue = Carbon::createFromTimestamp($searchValue);
        $value = Carbon::createFromTimestamp($value);

        // Check if both values represent the same day
        return $searchValue->isSameDay($value);
    }

    public static function mongodbCondition($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match a specific date in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The value to search for (can be a timestamp or any format convertible to a timestamp).
     * @return array The condition array for the query.
     */
    public static function postgresqlCondition(string $column, $searchValue): array
    {
        // Convert $searchValue to a timestamp
        $searchTimestamp = self::convertToTimestamp($searchValue);

        // Check if $searchValue couldn't be converted to a timestamp
        if (is_null($searchTimestamp)) {
            return [];
        }

        // Create a Carbon object from the timestamp to get the date in a specific format
        $searchDate = Carbon::createFromTimestamp($searchTimestamp)->toDateString();

        // Construct the condition for comparing the date in the column with the specified date
        return [new Expression("$column::date = :searchDate", [':searchDate' => $searchDate])];
    }

}
