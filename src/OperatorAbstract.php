<?php

namespace mvbsoft\queryManager;

use yii\base\BaseObject;

/**
 * OperatorAbstract is an abstract class representing a query operator.
 *
 * This class defines common properties and methods that all query operators should implement.
 *
 *
 * @property-read string $name - operator name
 * @property-read string $description - operator description
 * @property-read string $slug - operator slug
 * @property-read string $group - operator group
 *
 * @property-read array $toArray - return "name" "description" "slug" "group" as array with the same keys
 */
abstract class OperatorAbstract extends BaseObject
{
    // Constants defining operator groups
    public const GROUP_DATE     = 'date';
    public const GROUP_STRING   = 'string';
    public const GROUP_NUMBER   = 'number';
    public const GROUP_DEFAULT  = 'default';
    public const GROUP_BOOLEAN  = 'boolean';

    /**
     * Abstract method to get the operator's slug.
     *
     * @return string The slug of the operator.
     */
    abstract public static function slug() : string;

    /**
     * Abstract method to get the operator's name.
     *
     * @return string The name of the operator.
     */
    abstract public static function name() : string;

    /**
     * Abstract method to get the operator's group.
     *
     * @return string The group of the operator.
     */
    abstract public static function group() : string;

    /**
     * Abstract method to get the operator's description.
     *
     * @return string The description of the operator.
     */
    abstract public static function description() : string;

    /**
     * Get the slug of the operator.
     *
     * @return string The slug of the operator.
     */
    public function getSlug(): string
    {
        return static::slug();
    }

    /**
     * Get the name of the operator.
     *
     * @return string The name of the operator.
     */
    public function getName(): string
    {
        return static::name();
    }

    /**
     * Get the group of the operator.
     *
     * @return string The group of the operator.
     */
    public function getGroup(): string
    {
        return static::group();
    }

    /**
     * Get the description of the operator.
     *
     * @return string The description of the operator.
     */
    public function getDescription(): string
    {
        return static::description();
    }

    /**
     * Get an array representation of the operator.
     *
     * @return array An array representation of the operator.
     */
    public function getToArray(): array {
        return [
            'slug' => $this->slug,
            'group' => $this->group,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in PHP.
     *
     * @param mixed $searchValue The value to search in a PHP array.
     * @param string $column The column name (the value key in the PHP array of data).
     * @param mixed $value The field value to compare `$searchValue` with.
     * @return bool Returns true if `$searchValue` strictly equals `$value`, otherwise false.
     */
    abstract public static function phpCondition($searchValue, string $column, $value) : bool;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in MongoDB.
     *
     * @param mixed $searchValue The value to search in MongoDB.
     * @param string $column The column name (e.g., column name in the database).
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function mongodbCondition($searchValue, string $column) : array;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in PostgreSQL.
     *
     * @param mixed $searchValue The value to search in PostgreSQL.
     * @param string $column The column name (e.g., column name in the database).
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function postgresqlCondition($searchValue, string $column) : array;

    /**
     * Escape special characters in a string.
     *
     * @param string $value The string to escape special characters from.
     * @return string The string with special characters escaped.
     */
    public static function escapeSpecialChars(string $value): string
    {
        $specialChars = ['[', '\\', '^', '$', '.', '|', '?', '*', '+', '(', ')'];

        $escapedChars = ['\[', '\\\\', '\^', '\$', '\.', '\|', '\?', '\*', '\+', '\(', '\)'];

        return strtr($value, array_combine($specialChars, $escapedChars));
    }

}
