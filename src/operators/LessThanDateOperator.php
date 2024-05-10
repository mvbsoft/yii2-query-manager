<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class LessThanDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'less_than_date_operator';
    }

    public static function name(): string
    {
        return 'Less than';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Less than';
    }

    /**
     * Checks if the given value is less than the search value after converting them to timestamps.
     *
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue The search value to be compared against.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value is less than the search value after converting them to timestamps, otherwise returns false.
     */
    public static function phpConditions(string $column, $searchValue, array $data): bool
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

        // Check if $value is less than $searchValue
        return $value < $searchValue;
    }

    public static function mongodbConditions($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match timestamps in Postgres.
     *
     * @param string $column The column name.
     * @param mixed $searchValue The search value to compare against (can be a timestamp or any format convertible to a timestamp).
     * @return array The condition array for the query.
     */
    public static function postgresqlConditions(string $column, $searchValue): array
    {
        // Convert $searchValue to a timestamp
        $searchValue = self::convertToTimestamp($searchValue);

        // Check if $searchValue couldn't be converted to a timestamp
        if (is_null($searchValue)) {
            return [];
        }

        // Construct a condition array to compare the column with the timestamp value
        return ["<", $column, new Expression("to_timestamp($searchValue)")];
    }

}
