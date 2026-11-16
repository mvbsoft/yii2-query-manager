<?php

namespace mvbsoft\queryManager;

use Exception;
use yii\base\Component;
use yii\base\DynamicModel;
use yii\db\Connection;
use yii\mongodb\Query as MongodbQuery;
use yii\db\Query as PostgresqlQuery;

/**
 * QueryBuilder is a component for constructing complex queries.
 *
 * This class provides methods for generating conditions and queries based on query elements.
 *
 * @property-read string[] $operatorSlugs - array of all operators slug that available in the component
 * @property-read OperatorAbstract[] $operators - list of all operators objects that available in the component
 */
class QueryBuilder extends Component {

    // Constants defining logical conditions
    public const OR = 'OR';
    public const AND = 'AND';

    // Constants defining conditions types
    public const CONDITIONS_TYPE_PHP = 'php';
    public const CONDITIONS_TYPE_MONGODB = 'mongodb';
    public const CONDITIONS_TYPE_POSTGRESQL = 'postgresql';

    // Constants defining conditions element types
    public const CONDITIONS_ELEMENT_TYPE_NODES = 'nodes';
    public const CONDITIONS_ELEMENT_TYPE_EVENT = 'event';
    public const CONDITIONS_ELEMENT_TYPE_GROUP = 'group';
    public const CONDITIONS_ELEMENT_TYPE_INDIVIDUAL = 'individual';

    public const CONDITIONS_ELEMENT_KEYS_EVENT = ['id', 'condition', 'type', 'name', 'event', 'elements'];
    public const CONDITIONS_ELEMENT_KEYS_EVENT_REQUIRED = ['id', 'condition', 'type', 'event'];
    public const CONDITIONS_ELEMENT_KEYS_GROUP = ['id', 'condition', 'type', 'name', 'elements'];
    public const CONDITIONS_ELEMENT_KEYS_GROUP_REQUIRED = ['id', 'condition', 'type', 'elements'];
    public const CONDITIONS_ELEMENT_KEYS_INDIVIDUAL = ['id', 'condition', 'column', 'type', 'operator', 'value'];
    public const CONDITIONS_ELEMENT_KEYS_INDIVIDUAL_REQUIRED = ['id', 'condition', 'column', 'type', 'operator'];

    /** Extra key of an event block with event history: false negates the block ("not matching") */
    public const CONDITIONS_ELEMENT_KEY_MATCHING = 'matching';

    /**
     * @var bool Event history: an event holds a history (a list of its arrivals), and an event block
     * (`type: event`) is a history search — "there is an arrival where all conditions of the block match" (see
     * HistoryBlock), `matching: false` negates the block. Off by default: everything works as before, except an
     * empty event block. Blocks of type `nodes` are never affected.
     */
    public $eventHistory = false;

    /** @var int Depth of `nodes` blocks being handled: inside one everything keeps the old behaviour */
    private $_nodesDepth = 0;

    /**
     * @var string The folder path containing operator classes.
     */
    public $operatorsFolder = __DIR__ . DIRECTORY_SEPARATOR . 'operators';

    /**
     * @var string The namespace for operator classes.
     */
    public $operatorsNamespace = 'mvbsoft\queryManager\operators';

    /** @var OperatorAbstract[] Array containing loaded operator objects */
    private $_operators = [];

    /**
     * Returns an array of logical conditions.
     *
     * @return string[] Array of logical conditions.
     */
    public static function conditions(): array
    {
        return [
            self::OR,
            self::AND,
        ];
    }

    /**
     * Returns an array of condition types.
     *
     * @return string[] Array of condition types.
     */
    public static function conditionTypes(): array
    {
        return [
            self::CONDITIONS_TYPE_PHP,
            self::CONDITIONS_TYPE_MONGODB,
            self::CONDITIONS_TYPE_POSTGRESQL,
        ];
    }

    /**
     * Returns an array of conditions element types.
     *
     * @return string[] Array of conditions element types.
     */
    public static function conditionsElementsTypes(): array
    {
        return [
            self::CONDITIONS_ELEMENT_TYPE_NODES,
            self::CONDITIONS_ELEMENT_TYPE_EVENT,
            self::CONDITIONS_ELEMENT_TYPE_GROUP,
            self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL,
        ];
    }

    /**
     * Loads and returns operator objects.
     *
     * @throws Exception
     * @return OperatorAbstract[] Array of operator objects.
     */
    public function getOperators() : array {
        if(!empty($this->_operators)){
            return $this->_operators;
        }

        if (!is_dir($this->operatorsFolder)) {
            throw new Exception("Cannot find folder with operators");
        }

        $operatorFiles = glob($this->operatorsFolder . DIRECTORY_SEPARATOR . "*.php");

        foreach ($operatorFiles as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $className = $this->operatorsNamespace. '\\' . ucfirst($filename);

            if(!class_exists($className)){
                continue;
            }

            $classObject = new $className();

            if (!($classObject instanceof OperatorAbstract)) {
                throw new Exception("$className should be extends of NodeTypeAbstract");
            }

            $this->_operators[] = $classObject;
        }

        return $this->_operators;
    }

    /**
     * Returns an array of operator slugs.
     *
     * @return string[] Array of operator slugs.
     */
    public function getOperatorSlugs(): array
    {
        return array_map(function(OperatorAbstract $operator){
            return $operator->slug;
        }, $this->operators);
    }

    /**
     * Returns an operator object based on its slug.
     *
     * @param string $slug The slug of the operator.
     * @return OperatorAbstract|null The operator object, or null if not found.
     */
    public function getOperatorBySlug(string $slug): ?OperatorAbstract
    {
        // current() of an empty array is false, the declared result for "not found" is null
        return current(array_filter($this->operators, function (OperatorAbstract $operator) use ($slug) {
            return $operator->slug == $slug;
        })) ?: null;
    }

    /**
     * Creates a dynamic model for an event conditions.
     *
     * @return DynamicModel A dynamic model for an event conditions.
     */
    public function eventModel(): DynamicModel
    {
        // Create dynamic model with validation rules
        $model = new DynamicModel(self::CONDITIONS_ELEMENT_KEYS_EVENT);

        // Add validation rules
        $model->addRule(self::CONDITIONS_ELEMENT_KEYS_EVENT_REQUIRED, 'required');
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['name'], 'string', ['min' => 1, 'max' => 64]);
        $model->addRule(['event'], 'string', ['min' => 1, 'max' => 64]);
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['type'], 'in', ['range' => self::conditionsElementsTypes()]);
        $model->addRule(['elements'], ArrayValidator::class, ['maxSizeInBytes' => 1024 * 1024 * 2, 'skipOnEmpty' => false]);

        return $model;
    }

    /**
     * Creates a dynamic model for an event conditions with event history: the event model plus `matching`.
     *
     * @return DynamicModel
     */
    public function eventHistoryModel(): DynamicModel
    {
        $model = $this->eventModel();

        // Any value is accepted: only `false` itself negates the block, everything else (no key, null,
        // "false", 0) means matching, the same rule as generateConditions()
        $model->defineAttribute(self::CONDITIONS_ELEMENT_KEY_MATCHING);
        $model->addRule([self::CONDITIONS_ELEMENT_KEY_MATCHING], 'safe');

        return $model;
    }

    /**
     * Creates a dynamic model for a group conditions.
     *
     * @return DynamicModel A dynamic model for a group conditions.
     */
    public function groupModel(): DynamicModel
    {
        // Create dynamic model with validation rules
        $model = new DynamicModel(self::CONDITIONS_ELEMENT_KEYS_GROUP);

        // Add validation rules
        $model->addRule(self::CONDITIONS_ELEMENT_KEYS_GROUP_REQUIRED, 'required');
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['type'], 'in', ['range' => self::conditionsElementsTypes()]);
        $model->addRule(['name'], 'string', ['min' => 1, 'max' => 64]);
        $model->addRule(['elements'], ArrayValidator::class, ['maxSizeInBytes' => 1024 * 1024 * 2, 'skipOnEmpty' => false]);

        return $model;
    }

    /**
     * Creates a dynamic model for an individual conditions.
     *
     * @return DynamicModel A dynamic model for an individual conditions.
     */
    public function individualModel(): DynamicModel
    {
        // Create dynamic model with validation rules
        $model = new DynamicModel(self::CONDITIONS_ELEMENT_KEYS_INDIVIDUAL);

        // Add validation rules
        $model->addRule(self::CONDITIONS_ELEMENT_KEYS_INDIVIDUAL_REQUIRED, 'required');
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['column'], 'string', ['min' => 1, 'max' => 255]);
        $model->addRule(['type'], 'in', ['range' => self::conditionsElementsTypes()]);
        $model->addRule(['operator'], 'in', ['range' => $this->operatorSlugs]);

        // Add validation rule for array size
        $model->addRule(['value'], ArrayValidator::class, ['maxSizeInBytes' => 1024 * 1024 * 2, 'when' => function($model){
            return is_array($model->value);
        }]);

        return $model;
    }

    /**
     * Delete no needed keys from array of query elements
     *
     * @param array $elements The array of query elements.
     * @return array The array filtered conditions elements
     * @throws QueryBuilderException The filter of a `nodes` block returned no element (with event history).
     */
    public function filterConditions(array $elements): array
    {
        if($this->eventHistory){
            return $this->filterConditionsWithHistory($elements);
        }

        return $this->filterConditionsWithoutHistory($elements);
    }

    /**
     * filterConditions() without event history: unchanged old behaviour (nested elements of groups and blocks are
     * not filtered: the element itself is passed to the recursion instead of its elements).
     *
     * @param array $elements The array of query elements.
     * @return array The array filtered conditions elements
     */
    private function filterConditionsWithoutHistory(array $elements): array
    {
        foreach ($elements as $k => $element){
            $type = $element['type'] ?? null;

            if($type == self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL){
                // Removing the extra keys
                $element = array_intersect_key($element, array_flip(self::CONDITIONS_ELEMENT_KEYS_INDIVIDUAL));
            }

            if($type == self::CONDITIONS_ELEMENT_TYPE_GROUP){
                // Removing the extra keys
                $element = array_intersect_key($element, array_flip(self::CONDITIONS_ELEMENT_KEYS_GROUP));

                $element = $this->filterConditionsWithoutHistory($element);
            }

            if(in_array($type, [self::CONDITIONS_ELEMENT_TYPE_EVENT, self::CONDITIONS_ELEMENT_TYPE_NODES])){
                // Removing the extra keys
                $element = array_intersect_key($element, array_flip(self::CONDITIONS_ELEMENT_KEYS_EVENT));

                $element = $this->filterConditionsWithoutHistory($element);
            }

            $elements[$k] = $element;
        }

        return $elements;
    }

    /**
     * filterConditions() with event history: an event block keeps `matching`, and nested elements are filtered.
     * A block of type `nodes` is filtered the old way (filterConditionsWithoutHistory()).
     *
     * @param array $elements The array of query elements.
     * @return array The array filtered conditions elements
     * @throws QueryBuilderException The filter of a `nodes` block returned no element.
     */
    private function filterConditionsWithHistory(array $elements): array
    {
        $keys = [
            self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL => self::CONDITIONS_ELEMENT_KEYS_INDIVIDUAL,
            self::CONDITIONS_ELEMENT_TYPE_GROUP      => self::CONDITIONS_ELEMENT_KEYS_GROUP,
            self::CONDITIONS_ELEMENT_TYPE_EVENT      => array_merge(self::CONDITIONS_ELEMENT_KEYS_EVENT, [self::CONDITIONS_ELEMENT_KEY_MATCHING]),
        ];

        foreach ($elements as $k => $element){
            $type = is_array($element) && is_string($element['type'] ?? null) ? $element['type'] : null;

            if($type == self::CONDITIONS_ELEMENT_TYPE_NODES){
                $filtered = $this->filterConditionsWithoutHistory([$element]);

                if(!array_key_exists(0, $filtered)){
                    throw new QueryBuilderException('The filter of a "nodes" block returned no element.');
                }

                $elements[$k] = $filtered[0];

                continue;
            }

            if(!isset($keys[$type])){
                continue;
            }

            $element = array_intersect_key($element, array_flip($keys[$type]));

            if(is_array($element['elements'] ?? null)){
                $element['elements'] = $this->filterConditionsWithHistory($element['elements']);
            }

            $elements[$k] = $element;
        }

        return $elements;
    }

    /**
     * Validates query conditions.
     *
     * Inside a history search (an event block with `$eventHistory`) every column must start with `<event>.`, the
     * block can not contain another event or `nodes` block, and operators without history support are rejected.
     *
     * @param array $elements The array of query elements.
     * @param string $errorKey The main error key.
     * @return array The array of errors or empty array if there are no errors.
     */
    public function validateConditions(array $elements, string $errorKey = 'conditions'): array
    {
        return $this->validateElements($elements, $errorKey, null);
    }

    /**
     * validateConditions() of elements that are inside the history search of $historyEvent (null: outside one).
     *
     * @param array $elements
     * @param string $errorKey
     * @param string|null $historyEvent
     * @return array
     */
    private function validateElements(array $elements, string $errorKey, ?string $historyEvent): array
    {
        $errors = [];

        foreach ($elements as $key => $value){
            $type = $value['type'] ?? null;

            if(!is_array($value)){
                $errors["$errorKey.$key"] = ["Query element should be and array."];
            }
            elseif (!in_array($type, self::conditionsElementsTypes())){
                $errors["$errorKey.$key.type"] = ["Type is invalid."];
            }
            elseif (!is_null($historyEvent) && in_array($type, [self::CONDITIONS_ELEMENT_TYPE_EVENT, self::CONDITIONS_ELEMENT_TYPE_NODES])){
                $errors["$errorKey.$key.type"] = ["The event block \"$historyEvent\" can not contain another block."];
            }
            else{
                if($type == self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL){
                    $model = $this->individualModel();
                }
                elseif($type == self::CONDITIONS_ELEMENT_TYPE_GROUP){
                    $model = $this->groupModel();
                }
                elseif($this->isHistorySearch($value)){
                    $model = $this->eventHistoryModel();
                }
                elseif(in_array($type, [self::CONDITIONS_ELEMENT_TYPE_EVENT, self::CONDITIONS_ELEMENT_TYPE_NODES])){
                    $model = $this->eventModel();
                }
                else{
                    return $errors;
                }

                $model->setAttributes($value);

                $model->validate();

                if($model->hasErrors()){
                    foreach ($model->errors as $fieldAttribute => $fieldErrors){
                        $errors["$errorKey.$key.$fieldAttribute"] = $fieldErrors;
                    }

                    continue;
                }

                if($type == self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL){
                    $operator = $this->getOperatorBySlug($value['operator']);
                    $valueError = $operator ? $operator::validateValue($value['value'] ?? null) : null;

                    if(!is_null($valueError)){
                        $errors["$errorKey.$key.value"] = [$valueError];
                    }

                    if(!is_null($historyEvent) && $operator && !$operator::history()){
                        $errors["$errorKey.$key.operator"] = ["The operator is not supported in a history search."];
                    }

                    if(!is_null($historyEvent) && strpos($value['column'], "$historyEvent.") !== 0){
                        $errors["$errorKey.$key.column"] = ["The column of the event block \"$historyEvent\" must start with \"$historyEvent.\"."];
                    }
                }

                if(in_array($type, [self::CONDITIONS_ELEMENT_TYPE_GROUP, self::CONDITIONS_ELEMENT_TYPE_EVENT, self::CONDITIONS_ELEMENT_TYPE_NODES])){
                    // The model has checked that `event` is a string
                    $nestedEvent = $this->isHistorySearch($value) ? $value['event'] : $historyEvent;

                    $elementErrors = $this->nested($value, function () use ($value, $errorKey, $key, $nestedEvent) {
                        return $this->validateElements($value['elements'], "$errorKey.$key.elements", $nestedEvent);
                    });

                    foreach ($elementErrors as $ek => $nestedErrors){
                        $errors[$ek] = $nestedErrors;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Generates a condition for a given query element.
     *
     * @param array $queryElements The array of query elements.
     * @param string $conditionsType The condition type (php, mongodb, postgresql).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return PostgresqlQuery|MongodbQuery The generated query.
     */
    public function generateConditions(array $queryElements, string $conditionsType, array $data = [])
    {
        // Initialize query object based on conditions type
        if($conditionsType == self::CONDITIONS_TYPE_MONGODB){
            $query = new MongodbQuery();
        }
        else{
            $query = new PostgresqlQuery();
        }

        // Iterate over query elements to generate conditions
        foreach ($queryElements as $element){
            $type           = $element['type'];
            $condition      = $element['condition'];

            // Handle individual elements
            if($type == self::CONDITIONS_ELEMENT_TYPE_INDIVIDUAL){
                $column      = $element['column'];
                $operator    = $element['operator'];
                $searchValue = $element['value'] ?? null;

                $where = $this->generateWhere($operator, $conditionsType, $searchValue, $column, $data);

                if ((is_array($where) && !empty($where)) || (!is_array($where) && !is_null($where)))
                {
                    $this->addWhere($query, $condition, $where);
                }
            }

            // Handle event blocks with event history, and empty event blocks
            $historyBlock = $this->historyBlock($element);

            if(!is_null($historyBlock)){
                $where = $historyBlock->where($conditionsType, $data);

                if(!is_null($where)){
                    $this->addWhere($query, $condition, $where);
                }

                continue;
            }

            // Handle event elements
            if(in_array($type, [self::CONDITIONS_ELEMENT_TYPE_EVENT, self::CONDITIONS_ELEMENT_TYPE_NODES, self::CONDITIONS_ELEMENT_TYPE_GROUP])){
                $eventElements = $element['elements'];

                $eventQuery = $this->nested($element, function () use ($eventElements, $conditionsType, $data) {
                    return $this->generateConditions($eventElements, $conditionsType, $data);
                });

                // Not empty(): for PHP a group of one failed condition has where = 0, and it must still count as false
                $hasWhere = !is_null($eventQuery->where) && $eventQuery->where !== [];

                if($hasWhere){
                    $this->addWhere($query, $condition, $eventQuery->where);
                }
            }
        }

        return $query;
    }

    /**
     * Adds a condition to the query with AND / OR (any other value of the element `condition` adds nothing).
     *
     * @param PostgresqlQuery|MongodbQuery $query
     * @param mixed $condition AND or OR (not type-hinted: any stored value adds nothing, as before).
     * @param mixed $where
     */
    private function addWhere($query, $condition, $where)
    {
        if($condition == self::OR){
            $query->orWhere($where);
        }

        if($condition == self::AND){
            $query->andWhere($where);
        }
    }

    /**
     * Whether the element is a history search (its data is a list of records, see HistoryBlock).
     *
     * This is where element types are connected to history search; today only event blocks, turned on by
     * `$eventHistory`. Another type gets history search by answering true here (with its own setting) and by its
     * history key in historyBlock(). Never a block of type `nodes` or anything inside one: those keep the old behaviour.
     *
     * @param array $element The query element.
     * @return bool
     */
    private function isHistorySearch(array $element): bool
    {
        return ($element['type'] ?? null) == self::CONDITIONS_ELEMENT_TYPE_EVENT && $this->_nodesDepth == 0 && $this->eventHistory;
    }

    /**
     * The HistoryBlock for an element, or null when the element keeps the old handling.
     *
     * An event block: the history key is the event name, records are arrivals of the event. With history search
     * (isHistorySearch()) every event block is handled here; without it only an empty event block ("the client has
     * data of the event").
     *
     * @param array $element The query element.
     * @return HistoryBlock|null
     */
    private function historyBlock(array $element): ?HistoryBlock
    {
        if(($element['type'] ?? null) != self::CONDITIONS_ELEMENT_TYPE_EVENT || $this->_nodesDepth > 0){
            return null;
        }

        $key = $element['event'] ?? null;
        $elements = $element['elements'] ?? [];

        // A malformed block (no event name) keeps the old behaviour too
        if(!is_string($key) || $key === '' || !is_array($elements)){
            return null;
        }

        $history = $this->isHistorySearch($element);

        if(!$history && HistoryBlock::hasConditions($elements)){
            return null;
        }

        // Without history `matching` is ignored, as before; only an explicit false negates the block
        $matching = !$history || ($element[self::CONDITIONS_ELEMENT_KEY_MATCHING] ?? true) !== false;

        return new HistoryBlock($this, $key, $elements, $history, $matching);
    }

    /**
     * Runs the callback that handles the children of the element, marking when they are inside a block of type
     * `nodes` (old behaviour).
     *
     * @param array $element The element whose children the callback handles.
     * @param callable $callback
     * @return mixed The result of the callback.
     */
    private function nested(array $element, callable $callback)
    {
        if(($element['type'] ?? null) != self::CONDITIONS_ELEMENT_TYPE_NODES){
            return $callback();
        }

        $this->_nodesDepth++;

        try {
            return $callback();
        } finally {
            $this->_nodesDepth--;
        }
    }

    /**
     * Executes a PHP query based on the provided query elements and array data.
     *
     * @param array $queryElements The array of query elements.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return bool The result of the PHP query execution.
     */
    public function phpQuery(array $queryElements, array $data): bool
    {
        // Generate the conditions for the query
        $query = $this->generateConditions($queryElements,  self::CONDITIONS_TYPE_PHP,  $data);

        return $this->evaluatePhpWhere($query->where);
    }

    /**
     * Evaluates the PHP conditions built by generateConditions() (a tree of 1 / 0 joined by AND / OR).
     *
     * @param mixed $where The `where` of the generated query.
     * @return bool False for an empty condition.
     */
    public function evaluatePhpWhere($where): bool
    {
        // Create a query builder instance
        $queryBuilder = new \yii\db\QueryBuilder(new Connection());

        // Initialize parameters array
        $params = [];

        // Build the WHERE clause of the query
        $buildWhere = $queryBuilder->buildWhere($where, $params);

        // Replace placeholders and logical operators in the WHERE clause
        $buildWhere = str_replace(["WHERE ", "(1)", "(0)", "AND", "OR"], ["", 1, 0, "&&", "||"], $buildWhere);

        // Initialize the result variable
        $result = false;

        // Evaluate the PHP expression
        if(!empty($buildWhere)){
            eval('$result = boolval('. $buildWhere .');');
        }

        return $result;
    }

    /**
     * Generates a MongoDB query based on the provided query elements.
     *
     * @param array $queryElements The array of query elements.
     * @return MongodbQuery The generated MongoDB query.
     */
    public function mongodbQuery(array $queryElements = []): MongodbQuery
    {
        return $this->generateConditions($queryElements,  self::CONDITIONS_TYPE_MONGODB);
    }

    /**
     * Generates a PostgreSQL query based on the provided query elements.
     *
     * @param array $queryElements The array of query elements.
     * @return PostgresqlQuery The generated PostgreSQL query.
     */
    public function postgresqlQuery(array $queryElements = []): PostgresqlQuery
    {
        return $this->generateConditions($queryElements,  self::CONDITIONS_TYPE_POSTGRESQL);
    }

    /**
     * Generates a WHERE clause or conditions array for a given operator, conditions type, search value, and column.
     *
     * @param string $operatorSlug The operator slug.
     * @param string $conditionsType The conditions type (php, mongodb, postgresql).
     * @param mixed $searchValue The value to search for.
     * @param string $column The column where the search is performed.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return int|array|null The generated conditions.
     */
    public function generateWhere(string $operatorSlug, string $conditionsType, $searchValue, string $column, array $data = []) {
        // Get the operator object by slug
        $operator = $this->getOperatorBySlug($operatorSlug);

        // Unknown operator: matches nothing in every backend (like an invalid search value). Skipping it instead
        // would widen "A AND <unknown>" to just A.
        if(!$operator instanceof OperatorAbstract){
            if($conditionsType == self::CONDITIONS_TYPE_PHP){
                return 0;
            }

            if($conditionsType == self::CONDITIONS_TYPE_MONGODB){
                return OperatorAbstract::mongodbMatchNothing($column);
            }

            return OperatorAbstract::postgresqlMatchNothing();
        }

        // Generate conditions based on conditions type
        if($conditionsType == self::CONDITIONS_TYPE_PHP){
            return intval($operator::phpConditions($column,$searchValue,  $data));
        }

        if($conditionsType == self::CONDITIONS_TYPE_MONGODB){
            return $operator::mongodbConditions($column, $searchValue);
        }

        if($conditionsType == self::CONDITIONS_TYPE_POSTGRESQL){
            return $operator::postgresqlConditions($column, $searchValue);
        }

        return null;
    }

}
