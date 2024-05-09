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
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @param string $column The column name (the value key in the PHP array of data).
     * @return bool Returns true if `$searchValue` strictly equals `$value`, otherwise false.
     */
    abstract public static function phpCondition(string $column, $searchValue, array $data) : bool;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in MongoDB.
     *
     * @param string $column The column name (e.g., column name in the database).
     * @param mixed $searchValue The value to search in MongoDB.
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function mongodbCondition(string $column, $searchValue) : array;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in PostgreSQL.
     *
     * @param string $column The column name (e.g., column name in the database).
     * @param mixed $searchValue The value to search in PostgreSQL.
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function postgresqlCondition(string $column, $searchValue) : array;

    /**
     * Retrieves a value from a PHP array using the specified column name, simulating a database query.
     *
     * @param string $column The column name to retrieve the value from.
     * @param array $data The PHP array containing the data.
     * @return mixed|null The value from the PHP array corresponding to the column name, or null if not found.
     */
    public static function getValue(string $column, array $data){
        return $data[$column] ?? null;
    }

    public static function convertToTimestamp($date): ?int
    {
        if(is_numeric($date)){
            $date = intval($date);
        }

        if(is_string($date)){
            $date = strtotime($date);
        }

        if(!is_integer($date)){
            $date = null;
        }

        return $date;
    }

}
