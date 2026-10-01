<?php

namespace mvbsoft\queryManager;

use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
use yii\base\BaseObject;
use yii\db\Expression;

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

    // Date search values are accepted for years 0001..9999 only (PostgreSQL cannot represent much larger values)
    public const TIMESTAMP_MIN = -62135596800;
    public const TIMESTAMP_MAX = 253402300799;

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
    abstract public static function phpConditions(string $column, $searchValue, array $data) : bool;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in MongoDB.
     *
     * @param string $column The column name (e.g., column name in the database).
     * @param mixed $searchValue The value to search in MongoDB.
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function mongodbConditions(string $column, $searchValue) : array;

    /**
     * Compares the `$searchValue` with `$fieldValue` using strict equality check in PostgreSQL.
     *
     * @param string $column The column name (e.g., column name in the database).
     * @param mixed $searchValue The value to search in PostgreSQL.
     * @return array Returns an array of where condition for using in Yii2 Query Builder.
     */
    abstract public static function postgresqlConditions(string $column, $searchValue) : array;

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

    /**
     * Convert a date or timestamp to a UNIX timestamp.
     *
     * @param mixed $date The date or timestamp to convert.
     * @return int|null The UNIX timestamp or null if conversion fails.
     */
    public static function convertToTimestamp($date): ?int
    {
        // Check if $date is already numeric
        if(is_numeric($date)){
            return intval($date);
        }

        // Check if $date is a string and convert it to a timestamp
        if(is_string($date)){
            $timestamp = strtotime($date);

            if($timestamp === false){
                return null;
            }

            return $timestamp;
        }

        return null;
    }

    /**
     * Convert a date or timestamp to a UNIX timestamp for mongodb.
     *
     * @param mixed $date The date or timestamp to convert.
     * @return UTCDateTime|null
     */
    public static function convertToMongoDate($date): ?UTCDateTime
    {
        $timestamp = self::convertToTimestamp($date);

        if(is_null($timestamp)){
            return null;
        }

        return new UTCDateTime($timestamp * 1000);
    }

    /**
     * Convert a numeric value (int, float or numeric string such as "5.70") to int|float without truncation.
     *
     * All three backends compare numbers exactly, so "5.7" never equals 5.
     *
     * @param mixed $value The value to convert.
     * @return int|float|null The number or null if the value is not numeric.
     */
    public static function convertToNumber($value)
    {
        if(!is_numeric($value)){
            return null;
        }

        $number = $value + 0;

        // "1e999" is numeric but becomes INF — not a number any backend can compare with
        if(is_float($number) && !is_finite($number)){
            return null;
        }

        return $number;
    }

    /**
     * Convert a date search value to a UNIX timestamp, accepting only years 0001..9999
     * (a timestamp every backend — PHP, MongoDB, PostgreSQL — can represent).
     *
     * @param mixed $date A timestamp or any string strtotime() understands.
     * @return int|null The UNIX timestamp or null if the value is not a usable date.
     */
    public static function convertToSearchTimestamp($date): ?int
    {
        // Check the number itself before intval(): intval() turns INF / NAN / "1e999" into 0 (1970-01-01)
        // and wraps floats beyond the integer range back into it
        if(is_numeric($date)){
            $number = $date + 0;

            if(!is_finite($number) || !self::isTimestampInRange($number)){
                return null;
            }
        }

        $timestamp = self::convertToTimestamp($date);

        if(is_null($timestamp) || !self::isTimestampInRange($timestamp)){
            return null;
        }

        return $timestamp;
    }

    /**
     * Whether the timestamp is within years 0001..9999.
     *
     * @param int|float $timestamp
     * @return bool
     */
    public static function isTimestampInRange($timestamp): bool
    {
        return $timestamp >= self::TIMESTAMP_MIN && $timestamp <= self::TIMESTAMP_MAX;
    }

    /**
     * Convert a value to a string the way string operators read it: null (or a missing value) is an empty string,
     * other scalars are cast with strval().
     *
     * @param mixed $value The value to convert.
     * @return string|null The string or null if the value is not scalar (array, object).
     */
    public static function convertToString($value): ?string
    {
        if(is_null($value)){
            return '';
        }

        if(!is_scalar($value)){
            return null;
        }

        return strval($value);
    }

    /**
     * Case-insensitive match of a literal text in a value (used by contains / start with / end with).
     *
     * Unicode aware (Київ = київ), like MongoDB and PostgreSQL ILIKE. If either string is not valid UTF-8,
     * the match is byte-wise, exactly as it was before Unicode support (no warning, no exception).
     *
     * @param string $searchValue The literal text.
     * @param string $value The value to search in.
     * @param string $prefix '^' to anchor at the start or ''.
     * @param string $suffix '\z' to anchor at the very end or ''.
     * @return bool
     */
    public static function phpLiteralMatch(string $searchValue, string $value, string $prefix = '', string $suffix = ''): bool
    {
        $flags = self::isUtf8($searchValue) && self::isUtf8($value) ? 'iu' : 'i';

        return preg_match('/' . $prefix . preg_quote($searchValue, '/') . $suffix . '/' . $flags, $value) === 1;
    }

    /**
     * Whether the string is valid UTF-8 (checked without warnings).
     *
     * MongoDB and PostgreSQL reject invalid UTF-8 with an error, so string operators match nothing for such
     * a search value there instead of failing the whole query.
     *
     * @param string $value
     * @return bool
     */
    public static function isUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    /**
     * Escape LIKE wildcards (% and _) and the escape character itself, so the text is matched literally.
     *
     * @param string $searchValue The literal text.
     * @return string
     */
    public static function postgresqlLikeEscape(string $searchValue): string
    {
        return strtr($searchValue, ['\\' => '\\\\', '%' => '\%', '_' => '\_']);
    }

    /**
     * Start and end (inclusive) UNIX timestamps of the calendar day that contains $timestamp,
     * in the PHP default timezone.
     *
     * @param int $timestamp
     * @return array ['from' => int, 'to' => int]
     */
    public static function dayRange(int $timestamp): array
    {
        $date = Carbon::createFromTimestamp($timestamp);

        return [
            'from' => $date->copy()->startOfDay()->timestamp,
            'to'   => $date->copy()->endOfDay()->timestamp,
        ];
    }

    /**
     * Seconds since the start of the day (wall clock, PHP default timezone): hour * 3600 + minute * 60 + second.
     *
     * Same formula as the MongoDB ($hour/$minute/$second) and PostgreSQL (EXTRACT) conditions use.
     *
     * @param int $timestamp
     * @return int
     */
    public static function secondsOfDay(int $timestamp): int
    {
        $date = Carbon::createFromTimestamp($timestamp);

        return $date->hour * 3600 + $date->minute * 60 + $date->second;
    }

    /**
     * MongoDB condition that matches no document.
     *
     * Returned where phpConditions() rejects every row (e.g. an invalid search value), so the condition
     * is not silently dropped and MongoDB selects exactly the rows PHP would accept.
     *
     * @param string $column The column name.
     * @return array
     */
    public static function mongodbMatchNothing(string $column): array
    {
        return [$column => ['$in' => []]];
    }

    /**
     * MongoDB condition that matches every document (including those without the column).
     *
     * Returned where phpConditions() accepts every row, so the condition keeps its meaning inside OR.
     *
     * @param string $column The column name.
     * @return array
     */
    public static function mongodbMatchAll(string $column): array
    {
        return [$column => ['$nin' => []]];
    }

    /**
     * PostgreSQL condition that matches no row. See mongodbMatchNothing().
     *
     * @return array
     */
    public static function postgresqlMatchNothing(): array
    {
        return ['and', new Expression('1 = 0')];
    }

    /**
     * PostgreSQL condition that matches every row. See mongodbMatchAll().
     *
     * @return array
     */
    public static function postgresqlMatchAll(): array
    {
        return ['and', new Expression('1 = 1')];
    }

    /**
     * MongoDB condition: the date is within the whole seconds [from, to].
     *
     * PHP reads dates with whole-second precision (a fraction is dropped), so the upper bound is "before the next
     * second": a stored 23:59:59.600 belongs to the second 23:59:59 in every backend.
     *
     * @param string $column The column name.
     * @param int $from First second (inclusive).
     * @param int $to Last second (inclusive).
     * @return array
     */
    public static function mongodbDateRange(string $column, int $from, int $to): array
    {
        return [
            $column => [
                '$gte' => self::convertToMongoDate($from),
                '$lt'  => self::convertToMongoDate($to + 1)
            ]
        ];
    }

    /**
     * PostgreSQL condition: the date is within the whole seconds [from, to]. See mongodbDateRange().
     *
     * @param string $column The column name.
     * @param int $from First second (inclusive).
     * @param int $to Last second (inclusive).
     * @return array
     */
    public static function postgresqlDateRange(string $column, int $from, int $to): array
    {
        return ['and', ['>=', $column, self::postgresqlTimestamp($from)], ['<', $column, self::postgresqlTimestamp($to + 1)]];
    }

    /**
     * MongoDB aggregation expressions (for `$expr` / `$and`): the column holds a BSON date within years 0001..9999,
     * i.e. a date PHP can read. Anything else never matches instead of failing the whole query.
     *
     * @param string $column The column name.
     * @return array List of expressions.
     */
    public static function mongodbExprIsDate(string $column): array
    {
        return [
            ['$eq' => [['$type' => '$' . $column], 'date']],
            ['$gte' => ['$' . $column, self::convertToMongoDate(self::TIMESTAMP_MIN)]],
            ['$lt' => ['$' . $column, self::convertToMongoDate(self::TIMESTAMP_MAX + 1)]],
        ];
    }

    /**
     * PostgreSQL condition: the column holds a date within years 0001..9999 (see mongodbExprIsDate()).
     *
     * @param string $column The column name.
     * @return array
     */
    public static function postgresqlDateInRange(string $column): array
    {
        return self::postgresqlDateRange($column, self::TIMESTAMP_MIN, self::TIMESTAMP_MAX);
    }

    /**
     * PostgreSQL timestamp literal for a UNIX timestamp.
     *
     * @param int $timestamp
     * @return Expression
     */
    public static function postgresqlTimestamp(int $timestamp): Expression
    {
        return new Expression("to_timestamp($timestamp)");
    }

    /**
     * PostgreSQL numeric literal. A literal (not a bound parameter) keeps "integer_column = 5.7"
     * a numeric comparison instead of failing on the cast of '5.7' to integer.
     *
     * @param int|float $number A value returned by convertToNumber().
     * @return Expression
     */
    public static function postgresqlNumber($number): Expression
    {
        return new Expression(is_float($number) ? var_export($number, true) : (string)$number);
    }

}
