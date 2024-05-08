<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;

class CurrentDateOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'current_date_operator';
    }

    public static function name(): string
    {
        return 'Current date';
    }

    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    public static function description(): string
    {
        return 'Current date';
    }

    /**
     * Checks if the given value represents today's date.
     *
     * @param mixed $searchValue Not used in this function.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value represents today's date, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is a scalar value (string, number, or boolean)
        if (!is_scalar($value)) {
            return false; // If not, return false
        }

        // Convert $value to a timestamp
        $value = self::convertToTimestamp($value);

        // Check if the conversion was successful
        if(is_null($value)){
            return false; // If not, return false
        }

        // Create a Carbon object from the timestamp and check if it represents today's date
        return Carbon::createFromTimestamp($value)->isToday();
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
