<?php

namespace mvbsoft\queryManager\operators;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use mvbsoft\queryManager\OperatorAbstract;
use yii\db\Expression;

class DaysOfWeekOperator extends OperatorAbstract
{
    // Return the slug of the operator (unique identifier)
    public static function slug(): string
    {
        return 'days_of_week_operator';
    }

    // Return the human-readable name of the operator
    public static function name(): string
    {
        return 'Days Of Week';
    }

    // Return the group of the operator (Date filter in this case)
    public static function group(): string
    {
        return parent::GROUP_DATE;
    }

    // Return a brief description of the operator's functionality
    public static function description(): string
    {
        return 'Date filtering based on the days of the week';
    }

    // Apply the filtering conditions for PHP-based queries
    public static function phpConditions(string $column, $searchValue, array $data): bool
    {
        // Prepare the search value (array of days of the week)
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // Return false if the prepared search value is empty (invalid input)
        if(empty($preparedSearchValue)){
            return false;
        }

        // Get the value for comparison from the data array (assumed to be a date)
        $value = self::getValue($column, $data);

        // Convert the value into a Carbon instance (date object)
        $valueDate = Carbon::parse($value);

        // Check if the value's day of the week is in the search range
        return in_array($valueDate->dayOfWeek, $preparedSearchValue);
    }

    // Apply the filtering conditions for MongoDB queries
    public static function mongodbConditions(string $column, $searchValue) : array
    {
        // Prepare the search value (array of days of the week)
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // Return an empty array if the prepared search value is empty (invalid input)
        if(empty($preparedSearchValue)){
            return [];
        }

        return [
            '$expr' => [
                '$in' => [
                    ['$dayOfWeek' => '$' . $column],
                    $preparedSearchValue
                ]
            ]
        ];
    }

    // Apply the filtering conditions for PostgreSQL queries
    public static function postgresqlConditions(string $column, $searchValue) : array
    {
        // Prepare the search value (array of days of the week)
        $preparedSearchValue = self::_preparedSearchValue($searchValue);

        // Return an empty array if the prepared search value is empty (invalid input)
        if(empty($preparedSearchValue)){
            return [];
        }

        // Construct a PostgreSQL condition to check if the day of the week is in the list of days provided
        return [
            'in',
            new Expression("EXTRACT(DOW FROM $column)"),
            $preparedSearchValue
        ];
    }

    // Helper function to prepare the search value (validate and convert the array of days)
    private static function _preparedSearchValue($searchValue): array
    {
        // Ensure the search value is an array of integers between 0 and 6 (valid days of the week)
        if(
            !is_array($searchValue) ||
            array_diff($searchValue, range(0, 6))
        ){
            return []; // Return an empty array if validation fails
        }

        // Return the validated list of days of the week
        return $searchValue;
    }
}
