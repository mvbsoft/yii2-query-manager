<?php

namespace mvbsoft\queryManager;

use yii\db\Expression;
use yii\db\Query as PostgresqlQuery;
use yii\mongodb\Connection as MongodbConnection;
use yii\mongodb\QueryBuilder as MongodbQueryBuilder;

/**
 * Conditions over a history: a list of records kept under one key of the data.
 *
 * A history block means "there is a record where all conditions of the block match"; an empty block (no condition
 * inside) means "there is at least one record"; `matching: false` negates the block. The record is an object with the
 * same fields in every backend:
 *
 * - MongoDB: the key is a field of the document holding an array of records (`$elemMatch`);
 * - PostgreSQL 16+: the key is a `jsonb` column holding the same array (`EXISTS` over its elements);
 * - PHP: only `$data[<key>]` holds the records, as a list of arrays. No key, `null` and `[]` are no records; any other
 *   value is a QueryBuilderException naming the key: the caller builds the PHP data and must give it this format.
 *
 * Without a history (one set of values per key) only an empty block comes here, see presenceWhere().
 *
 * The class does not know what the history is: QueryBuilder::historyBlock() connects an element type to it (event
 * blocks: the key is the event name, records are arrivals of the event).
 */
class HistoryBlock
{
    /**
     * An operator slug that does not exist: QueryBuilder::generateWhere() makes it match nothing in every backend.
     * Replaces operators without history support (OperatorAbstract::history()) inside a record — the last of three
     * guards, after the UI and QueryBuilder::validateConditions()
     */
    private const MATCH_NOTHING_OPERATOR = '';

    /** Aliases of the PostgreSQL subquery that unpacks the records */
    private const POSTGRESQL_RECORD = 'qm_record';
    private const POSTGRESQL_VALUE = 'qm_value';

    /** @var int Counter for unique names of PostgreSQL parameters (several blocks can be in one query) */
    private static $_postgresqlParams = 0;

    /** @var QueryBuilder */
    private $_builder;

    /** @var string */
    private $_key;

    /** @var array */
    private $_elements;

    /** @var bool */
    private $_history;

    /** @var bool */
    private $_matching;

    /**
     * @param QueryBuilder $builder The builder that generates the conditions of the elements.
     * @param string $key The key of the history: MongoDB field, PostgreSQL `jsonb` column, PHP data key, and the
     * prefix of the block columns (`<key>.<field>`).
     * @param array $elements The conditions of the block.
     * @param bool $history Whether the data is a history. Without it only an empty block is handled here: MongoDB and
     * PHP check that there is data under the key (a non-empty array or object in MongoDB, a `<key>.*` key in PHP),
     * PostgreSQL skips the block.
     * @param bool $matching False negates the block.
     */
    public function __construct(QueryBuilder $builder, string $key, array $elements, bool $history, bool $matching = true)
    {
        $this->_builder  = $builder;
        $this->_key      = $key;
        $this->_elements = $elements;
        $this->_history  = $history;
        $this->_matching = $matching;
    }

    /**
     * Whether the elements contain at least one individual condition (at any depth).
     *
     * @param array $elements
     * @return bool
     */
    public static function hasConditions(array $elements): bool
    {
        foreach ($elements as $element) {
            if (($element['type'] ?? null) == QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL) {
                return true;
            }

            if (is_array($element['elements'] ?? null) && self::hasConditions($element['elements'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Condition of the block for QueryBuilder::generateConditions().
     *
     * @param string $conditionsType php, mongodb or postgresql.
     * @param array $data The data of the PHP check.
     * @return int|array|null 1/0 for PHP, a condition for MongoDB / PostgreSQL, null to skip the block.
     * @throws QueryBuilderException A column of the block has no `<key>.` prefix, the block contains another block,
     * the key can not be a PostgreSQL column, or the PHP data of the history is not a list of records.
     */
    public function where(string $conditionsType, array $data)
    {
        $where = self::hasConditions($this->_elements)
            ? $this->conditionsWhere($conditionsType, $data)
            : $this->presenceWhere($conditionsType, $data);

        if ($this->_matching || is_null($where)) {
            return $where;
        }

        return $this->negate($where, $conditionsType);
    }

    /**
     * Block with conditions (only for a history).
     *
     * @param string $conditionsType
     * @param array $data
     * @return int|array
     * @throws QueryBuilderException
     */
    private function conditionsWhere(string $conditionsType, array $data)
    {
        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_MONGODB) {
            return [$this->_key => ['$elemMatch' => $this->mongodbRecordCondition()]];
        }

        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_POSTGRESQL) {
            return $this->postgresqlRecordExists();
        }

        // Before the data checks: an invalid block throws whatever the data is, like in MongoDB / PostgreSQL
        $elements = $this->recordElements();

        return intval($this->phpHasRecord($elements, $this->phpRecords($data)));
    }

    /**
     * Empty block. With a history: "there is at least one record", a non-empty list. Without a history: "there is
     * data under the key", a non-empty array or object in MongoDB, a `<key>.*` key in PHP; PostgreSQL skips the block.
     *
     * @param string $conditionsType
     * @param array $data
     * @return int|array|Expression|null
     * @throws QueryBuilderException
     */
    private function presenceWhere(string $conditionsType, array $data)
    {
        $key = $this->_key;

        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_MONGODB) {
            // "<key>.0" exists only in a non-empty array
            $nonEmptyList = ["$key.0" => ['$exists' => true]];

            if ($this->_history) {
                return $nonEmptyList;
            }

            return ['$or' => [
                $nonEmptyList,
                [$key => ['$type' => 'object', '$ne' => (object)[]]],
            ]];
        }

        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_POSTGRESQL) {
            // Without a history the block columns belong to a table: nothing to check, the block is skipped
            if (!$this->_history) {
                return null;
            }

            // CASE, not AND: PostgreSQL does not promise to check jsonb_typeof() first, and jsonb_array_length() of a
            // non-array is an error. ELSE false: a missing value (NULL) must stay false under NOT
            $column = $this->postgresqlColumn();

            return new Expression("CASE WHEN jsonb_typeof($column) = 'array' THEN jsonb_array_length($column) > 0"
                . " ELSE false END");
        }

        if ($this->_history) {
            return intval(!empty($this->phpRecords($data)));
        }

        foreach (array_keys($data) as $dataKey) {
            if (strpos((string)$dataKey, "$key.") === 0) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Negated block (`matching: false`): no record matches / there is no data under the key.
     *
     * @param int|array|Expression $where
     * @param string $conditionsType
     * @return int|array
     */
    private function negate($where, string $conditionsType)
    {
        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_MONGODB) {
            // Also matches documents without the key
            return ['$nor' => [$where]];
        }

        if ($conditionsType == QueryBuilder::CONDITIONS_TYPE_POSTGRESQL) {
            return ['not', $where];
        }

        // Not ['not', ...]: phpQuery() evaluates the built expression with eval(), where NOT is a parse error
        return 1 - $where;
    }

    /**
     * The records of the PHP data: the list under the key. No key, `null` and `[]` are no records.
     *
     * @param array $data
     * @return array
     * @throws QueryBuilderException The value is not a list, or an element of the list is not an array.
     */
    private function phpRecords(array $data): array
    {
        $records = $data[$this->_key] ?? null;

        if (is_null($records)) {
            return [];
        }

        if (!is_array($records) || array_keys($records) !== array_keys(array_keys($records))) {
            throw new QueryBuilderException("History \"$this->_key\" must be a list of records.");
        }

        foreach ($records as $record) {
            if (!is_array($record)) {
                throw new QueryBuilderException("Every record of the history \"$this->_key\" must be an object.");
            }
        }

        return $records;
    }

    /**
     * Whether one of the records matches all conditions of the block.
     *
     * @param array $elements The conditions on one record (recordElements()).
     * @param array $records The records (phpRecords()).
     * @return bool
     */
    private function phpHasRecord(array $elements, array $records): bool
    {
        foreach ($records as $record) {
            $query = $this->_builder->generateConditions($elements, QueryBuilder::CONDITIONS_TYPE_PHP, $record);

            if ($this->_builder->evaluatePhpWhere($query->where)) {
                return true;
            }
        }

        return false;
    }

    /**
     * MongoDB condition on one record (the content of `$elemMatch`).
     *
     * QueryBuilder produces conditions in the Yii format (`['and', ...]`); `$elemMatch` needs MongoDB operators.
     *
     * @return array
     * @throws QueryBuilderException
     */
    private function mongodbRecordCondition(): array
    {
        $query = $this->_builder->generateConditions($this->recordElements(), QueryBuilder::CONDITIONS_TYPE_MONGODB);

        // buildCondition() never uses the connection, and an unopened Connection connects to nothing:
        // the object is here only because the constructor asks for one
        return (new MongodbQueryBuilder(new MongodbConnection()))->buildCondition($query->where);
    }

    /**
     * PostgreSQL condition: EXISTS over the records of the `jsonb` column named after the key.
     *
     * The records are unpacked into a subquery with one typed column per condition, so the operators work on
     * ordinary columns. A value of another JSON type than the operator expects is NULL (no value).
     *
     * @return array
     * @throws QueryBuilderException
     */
    private function postgresqlRecordExists(): array
    {
        $columns  = [];
        $elements = $this->postgresqlElements($this->recordElements(), $columns);
        $value    = self::POSTGRESQL_VALUE;
        $column   = $this->postgresqlColumn();

        $records = (new PostgresqlQuery())
            ->select($columns)
            ->from(new Expression("jsonb_array_elements(CASE WHEN jsonb_typeof($column) = 'array'"
                . " THEN $column ELSE '[]'::jsonb END) AS $value(v)"))
            ->where(new Expression("jsonb_typeof($value.v) = 'object'"));

        $query = $this->_builder->generateConditions($elements, QueryBuilder::CONDITIONS_TYPE_POSTGRESQL);

        $exists = (new PostgresqlQuery())
            ->select(new Expression('1'))
            ->from([self::POSTGRESQL_RECORD => $records])
            ->where($query->where);

        return ['exists', $exists];
    }

    /**
     * Replace the columns of the conditions with the typed columns of the record subquery.
     *
     * @param array $elements Elements with columns relative to the record (see recordElements()).
     * @param array $columns Collected select of the subquery: alias => expression.
     * @return array
     */
    private function postgresqlElements(array $elements, array &$columns): array
    {
        foreach ($elements as $k => $element) {
            if (($element['type'] ?? null) == QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL) {
                $alias = 'c' . count($columns);
                $columns[$alias] = $this->postgresqlValue($element['column'], $element['operator']);
                $elements[$k]['column'] = self::POSTGRESQL_RECORD . ".$alias";
            }
            elseif (is_array($element['elements'] ?? null)) {
                $elements[$k]['elements'] = $this->postgresqlElements($element['elements'], $columns);
            }
        }

        return $elements;
    }

    /**
     * Typed value of a field of the record, by the group of the operator that reads it.
     *
     * @param string $field
     * @param string $operatorSlug
     * @return Expression
     */
    private function postgresqlValue(string $field, string $operatorSlug): Expression
    {
        $operator = $this->_builder->getOperatorBySlug($operatorSlug);
        $group    = $operator ? $operator->group : OperatorAbstract::GROUP_DEFAULT;
        // The field name is a bound parameter, never part of the SQL text
        $param    = ':qm_field_' . self::$_postgresqlParams++;
        $params   = [$param => $field];
        $json     = self::POSTGRESQL_VALUE . ".v -> CAST($param AS text)";
        $text     = self::POSTGRESQL_VALUE . ".v ->> CAST($param AS text)";

        switch ($group) {
            case OperatorAbstract::GROUP_STRING:
                return new Expression("CASE WHEN jsonb_typeof($json) = 'string' THEN $text END", $params);
            case OperatorAbstract::GROUP_NUMBER:
                return new Expression("CASE WHEN jsonb_typeof($json) = 'number' THEN ($text)::numeric END", $params);
            case OperatorAbstract::GROUP_BOOLEAN:
                return new Expression("CASE WHEN jsonb_typeof($json) = 'boolean' THEN ($text)::boolean END", $params);
            case OperatorAbstract::GROUP_DATE:
                return new Expression("CASE WHEN jsonb_typeof($json) = 'string' AND pg_input_is_valid($text, 'timestamptz')"
                    . " THEN ($text)::timestamptz END", $params);
            default:
                // is null / is not null: any JSON value, JSON null is NULL
                return new Expression($text, $params);
        }
    }

    /**
     * The `jsonb` column of the history, quoted.
     *
     * The name is written into the SQL text (a column can not be a parameter), so only names Yii quotes safely are
     * accepted: letters, digits, `_` and `-`.
     *
     * @return string
     * @throws QueryBuilderException
     */
    private function postgresqlColumn(): string
    {
        if (!preg_match('/^[\w\-]+$/', $this->_key)) {
            throw new QueryBuilderException("History \"$this->_key\" can not be a PostgreSQL column: use letters, digits, \"_\" and \"-\".");
        }

        return "[[$this->_key]]";
    }

    /**
     * Elements of the block as conditions on one record: `<key>.` is cut from the columns, and operators that can not
     * work inside a record match nothing.
     *
     * @param array|null $elements Elements to convert, the block elements by default.
     * @return array
     * @throws QueryBuilderException A column has no `<key>.` prefix, or the block contains another block.
     */
    private function recordElements(?array $elements = null): array
    {
        $elements = $elements ?? $this->_elements;
        $prefix   = $this->_key . '.';

        foreach ($elements as $k => $element) {
            $type = $element['type'] ?? null;

            if (in_array($type, [QueryBuilder::CONDITIONS_ELEMENT_TYPE_EVENT, QueryBuilder::CONDITIONS_ELEMENT_TYPE_NODES], true)) {
                throw new QueryBuilderException("The history block \"$this->_key\" can not contain another block.");
            }

            if ($type == QueryBuilder::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL) {
                $column = (string)($element['column'] ?? '');

                if (strpos($column, $prefix) !== 0) {
                    throw new QueryBuilderException("Column \"$column\" of the history block \"$this->_key\" must start with \"$prefix\".");
                }

                $elements[$k]['column'] = substr($column, strlen($prefix));

                $operator = $this->_builder->getOperatorBySlug((string)($element['operator'] ?? ''));

                if ($operator && !$operator::history()) {
                    $elements[$k]['operator'] = self::MATCH_NOTHING_OPERATOR;
                }
            }
            elseif (is_array($element['elements'] ?? null)) {
                $elements[$k]['elements'] = $this->recordElements($element['elements']);
            }
        }

        return $elements;
    }
}
