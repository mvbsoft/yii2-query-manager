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
     * @param mixed $searchValue An array containing two elements representing the start and end of the range.
     * @param string $column The column name (unused in this function).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool Returns true if the integer falls within the specified range, false otherwise.
     */
    public static function phpCondition($searchValue, string $column, array $data): bool
    {
        // Get value from array
        $value = self::getValue($column, $data);

        // Перевіряємо, чи $searchValue є масивом і має рівно два елементи
        if(!is_array($searchValue) || count($searchValue) !== 2){
            return false; // Якщо ні, повертаємо false
        }

        // Отримуємо значення початку та кінця діапазону
        $fromInt = $searchValue[0];
        $toInt = $searchValue[1];

        // Перевіряємо, чи всі значення є числами
        if(!is_numeric($fromInt) || !is_numeric($toInt) || !is_numeric($value)){
            return false; // Якщо ні, повертаємо false
        }

        // Перетворюємо значення на цілі числа
        $fromInt = intval($fromInt);
        $toInt = intval($toInt);
        $value = intval($value);

        // Перевіряємо, чи $value знаходиться між $fromInt і $toInt
        return $value >= $fromInt && $value <= $toInt;
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
