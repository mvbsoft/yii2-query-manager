<?php

namespace mvbsoft\queryManager;

use Exception;
use Yii;
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
    public const CONDITION_OR = 'OR';
    public const CONDITION_AND = 'AND';

    // Constants defining condition types
    public const CONDITION_TYPE_PHP = 'php';
    public const CONDITION_TYPE_MONGODB = 'mongodb';
    public const CONDITION_TYPE_POSTGRESQL = 'postgresql';

    // Constants defining condition element types
    public const CONDITION_ELEMENT_TYPE_GROUP = 'group';
    public const CONDITION_ELEMENT_TYPE_INDIVIDUAL = 'individual';

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
            self::CONDITION_OR,
            self::CONDITION_AND,
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
            self::CONDITION_TYPE_PHP,
            self::CONDITION_TYPE_MONGODB,
            self::CONDITION_TYPE_POSTGRESQL,
        ];
    }

    /**
     * Returns an array of condition element types.
     *
     * @return string[] Array of condition element types.
     */
    public static function conditionElementsTypes(): array
    {
        return [
            self::CONDITION_ELEMENT_TYPE_GROUP,
            self::CONDITION_ELEMENT_TYPE_INDIVIDUAL,
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
        return current(array_filter($this->operators, function (OperatorAbstract $operator) use ($slug) {
            return $operator->slug == $slug;
        }));
    }

    /**
     * Creates a dynamic model for a group condition.
     *
     * @return DynamicModel A dynamic model for a group condition.
     */
    public function groupModal(): DynamicModel
    {
        // Create dynamic model with validation rules
        $model = new DynamicModel(['id', 'column', 'type', 'operator', 'value']);

        // Add validation rules
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition', 'type', 'name'], 'required');
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['type'], 'in', ['range' => self::conditionElementsTypes()]);
        $model->addRule(['name'], 'string', ['min' => 1, 'max' => 64]);
        $model->addRule(['elements'], ArrayValidator::class, ['maxSizeInBytes' => 1024 * 1024 * 2, 'skipOnEmpty' => false]);

        return $model;
    }

    /**
     * Creates a dynamic model for an individual condition.
     *
     * @return DynamicModel A dynamic model for an individual condition.
     */
    public function individualModel(): DynamicModel
    {
        // Create dynamic model with validation rules
        $model = new DynamicModel(['id', 'condition', 'column', 'type', 'operator', 'value']);

        // Add validation rules
        $model->addRule(['condition', 'column', 'type', 'operator', 'value'], 'required');
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['column'], 'string', ['min' => 1, 'max' => 255]);
        $model->addRule(['type'], 'in', ['range' => self::conditionElementsTypes()]);
        $model->addRule(['operator'], 'in', ['range' => $this->operatorSlugs]);

        // Add custom validation rule for value
        $model->addRule(['value'], function ($attribute) use ($model) {
            $value = $model->$attribute;

            if(!is_string($value) && !is_integer($value) && !is_array($value)){
                $model->addError($attribute, 'Value can be string or integer or array');
            }
        });

        // Add validation rule for array size
        $model->addRule(['value'], ArrayValidator::class, ['maxSizeInBytes' => 100, 'when' => function($model){
            return is_array($model->value);
        }]);

        return $model;
    }

    /**
     * Validates query conditions.
     *
     * @param array $queryElements The array of query elements.
     * @param string $errorKey The main error key.
     * @return array The array of errors or empty array if there are no errors.
     */
    public function validateConditions(array $queryElements, string $errorKey = 'conditions'): array
    {
        $errors = [];

        foreach ($queryElements as $key => $value){
            $type = $value['type'] ?? null;

            if($type == self::CONDITION_ELEMENT_TYPE_INDIVIDUAL){
                $model = $this->individualModel();
            }
            elseif($type == self::CONDITION_ELEMENT_TYPE_GROUP){
                $model = $this->groupModal();
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
            }

            if($type == self::CONDITION_ELEMENT_TYPE_GROUP){
                $elementErrors = $this->validateConditions($value['elements'], "$errorKey.$key.elements");

                foreach ($elementErrors as $ek => $error){
                    $errors[$ek] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Generates a condition for a given query element.
     *
     * @param array $queryElements The array of query elements.
     * @param string $conditionType The condition type (php, mongodb, postgresql).
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return PostgresqlQuery|MongodbQuery The generated query.
     */
    public function generateCondition(array $queryElements, string $conditionType, array $data = [])
    {
        // Initialize query object based on condition type
        if($conditionType == self::CONDITION_TYPE_MONGODB){
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
            if($type == self::CONDITION_ELEMENT_TYPE_INDIVIDUAL){
                $column      = $element['column'];
                $operator    = $element['operator'];
                $searchValue = $element['value'];

                $where = $this->generateWhere($operator, $conditionType, $searchValue, $column, $data);

                if($condition == self::CONDITION_OR && !is_null($where)){
                    $query->orWhere($where);
                }

                if($condition == self::CONDITION_AND && !is_null($where)){
                    $query->andWhere($where);
                }
            }

            // Handle group elements
            if($type == self::CONDITION_ELEMENT_TYPE_GROUP){
                $groupElements = $element['elements'];

                $groupQuery = $this->generateCondition($groupElements, $conditionType, $data);

                if($condition == self::CONDITION_OR && !empty($groupQuery->where)){
                    $query->orWhere($groupQuery->where);
                }

                if($condition == self::CONDITION_AND && !empty($groupQuery->where)){
                    $query->andWhere($groupQuery->where);
                }
            }
        }

        return $query;
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
        // Create a query builder instance
        $queryBuilder = new \yii\db\QueryBuilder(new Connection());

        // Generate the condition for the query
        $query = $this->generateCondition($queryElements,  self::CONDITION_TYPE_PHP,  $data);

        // Initialize parameters array
        $params = [];

        // Build the WHERE clause of the query
        $buildWhere = $queryBuilder->buildWhere($query->where, $params);

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
    public function mongodbQuery(array $queryElements): MongodbQuery
    {
        return $this->generateCondition($queryElements,  self::CONDITION_TYPE_MONGODB);
    }

    /**
     * Generates a PostgreSQL query based on the provided query elements.
     *
     * @param array $queryElements The array of query elements.
     * @return PostgresqlQuery The generated PostgreSQL query.
     */
    public function postgresqlQuery(array $queryElements): PostgresqlQuery
    {
        return $this->generateCondition($queryElements,  self::CONDITION_TYPE_POSTGRESQL);
    }

    /**
     * Generates a WHERE clause or condition array for a given operator, condition type, search value, and column.
     *
     * @param string $operatorSlug The operator slug.
     * @param string $conditionType The condition type (php, mongodb, postgresql).
     * @param mixed $searchValue The value to search for.
     * @param string $column The column where the search is performed.
     * @param array $data The data used to generate a query from a PHP array. This array represents a row in the database.
     * @return int|array|null The generated condition.
     */
    public function generateWhere(string $operatorSlug, string $conditionType, $searchValue, string $column, array $data = []) {
        // Get the operator object by slug
        $operator = $this->getOperatorBySlug($operatorSlug);

        // If operator not found, return null
        if(!$operator instanceof OperatorAbstract){
            return null;
        }

        // Generate condition based on condition type
        if($conditionType == self::CONDITION_TYPE_PHP){
            return intval($operator::phpCondition($searchValue, $column, $data));
        }

        if($conditionType == self::CONDITION_TYPE_MONGODB){
            return $operator::mongodbCondition($searchValue, $column);
        }

        if($conditionType == self::CONDITION_TYPE_POSTGRESQL){
            return $operator::postgresqlCondition($searchValue, $column);
        }

        return null;
    }

}