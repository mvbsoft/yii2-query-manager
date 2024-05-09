<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

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
     * @param string $column The column name (unused in this function).
     * @param mixed $searchValue Not used in this function.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value represents today's date, otherwise returns false.
     */
    public static function phpCondition(string $column, $searchValue, array $data): bool
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

    public static function mongodbCondition($column, $searchValue) : array
    {
        return [];
    }

    /**
     * Generate a condition array for the query builder to match the current date.
     *
     * @param string $column The column name.
     * @param mixed $searchValue Optional search value (not used in this function).
     * @return array The condition array for the query.
     */
    public static function postgresqlCondition(string $column, $searchValue = null): array
    {
        // Return an expression to match the current date for the specified column
        return [new Expression("$column::date = CURRENT_DATE")];
    }

}
