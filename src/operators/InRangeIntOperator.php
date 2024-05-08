<?php

namespace mvbsoft\queryManager\operators;

use mvbsoft\queryManager\OperatorAbstract;

class InRangeIntOperator extends OperatorAbstract
{

    public static function slug(): string
    {
        return 'in_range_int_operator';
    }

    public static function name(): string
    {
        return 'In range';
    }

    public static function group(): string
    {
        return parent::GROUP_NUMBER;
    }

    public static function description(): string
    {
        return 'In range';
    }

    /**
     * Checks if a given value matches any of the numeric values in the provided array.
     *
     * @param mixed $searchValue An array containing numeric values to search for.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the value matches any of the numeric values in the array, otherwise returns false.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Check if $value is numeric
        if(!is_numeric($value)){
            return false; // If not, return false
        }

        // Convert $value to an integer
        $valueInt = intval($value);

        // Check if $searchValue is an array
        if(!is_array($searchValue)){
            return false; // If not, return false
        }

        // Iterate through each item in $searchValue
        foreach ($searchValue as $item){
            // Skip non-numeric items
            if(!is_numeric($item)){
                continue; // Move to the next iteration
            }

            // Convert the item to an integer
            $itemInt = intval($item);

            // Check if $valueInt matches $itemInt
            if($valueInt === $itemInt){
                return true; // If so, return true
            }
        }

        return false; // If no match found, return false
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
