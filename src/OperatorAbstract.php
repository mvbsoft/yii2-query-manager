<?php

namespace mvbsoft\queryManager;

use yii\base\BaseObject;

/**
 *
 * @property-read string $name
 * @property-read string $description
 * @property-read string $slug
 * @property-read string $group
 *
 * @property-read array $toArray
 */
abstract class OperatorAbstract extends BaseObject
{
    public const GROUP_DATE     = 'date';
    public const GROUP_STRING   = 'string';
    public const GROUP_NUMBER   = 'number';
    public const GROUP_DEFAULT  = 'default';
    public const GROUP_BOOLEAN  = 'boolean';

    abstract public static function slug() : string;

    abstract public static function name() : string;

    abstract public static function group() : string;

    abstract public static function description() : string;

    public function getSlug(): string
    {
        return static::slug();
    }

    public function getName(): string
    {
        return static::name();
    }

    public function getGroup(): string
    {
        return static::group();
    }

    public function getDescription(): string
    {
        return static::description();
    }

    public function getToArray(): array {
        return [
            'slug' => $this->slug,
            'group' => $this->group,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check.
     *
     * @param mixed $searchValue The value to search in php array.
     * @param string $column The column name (the value key in php array of data).
     * @param mixed $value The field value to compare `$searchValue` with.
     * @return bool Returns true if `$searchValue` strictly equals `$value`, otherwise false. Also, this boolean result
     *              can be using in Yii2 Query Builder.
     */
    abstract public static function phpCondition($searchValue, string $column, $value) : bool;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check.
     *
     * @param mixed $searchValue The value to search in mongodb.
     * @param string $column The column name (e.g., column name in the database).
     * @return array Returns array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function mongodbCondition($searchValue, string $column) : array;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check.
     *
     * @param mixed $searchValue The value to search in postgreSQL.
     * @param string $column The column name (e.g., column name in the database).
     * @return array Returns array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function postgresqlCondition($searchValue, string $column) : array;

    /**
     * @param string $value
     * @return string
     */
    public static function escapeSpecialChars(string $value): string
    {
        $specialChars = ['[', '\\', '^', '$', '.', '|', '?', '*', '+', '(', ')'];

        $escapedChars = ['\[', '\\\\', '\^', '\$', '\.', '\|', '\?', '\*', '\+', '\(', '\)'];

        return strtr($value, array_combine($specialChars, $escapedChars));
    }

}