<?php

namespace mvbsoft\queryManager;

use Exception;
use Yii;
use yii\base\Component;
use yii\base\DynamicModel;
use yii\mongodb\Query as MongodbQuery;
use yii\db\Query as PostgresqlQuery;

/**
 * @property-read string[] $operatorSlugs
 * @property-read OperatorAbstract[] $operators
 */
class QueryBuilder extends Component {

    public const CONDITION_OR = 'OR';
    public const CONDITION_AND = 'AND';
    public const CONDITION_TYPE_PHP = 'php';
    public const CONDITION_TYPE_MONGODB = 'mongodb';
    public const CONDITION_TYPE_POSTGRESQL = 'postgresql';
    public const CONDITION_ELEMENT_TYPE_GROUP = 'group';
    public const CONDITION_ELEMENT_TYPE_INDIVIDUAL = 'individual';

    /**
     * @var string
     */
    public $operatorsFolder = __DIR__ . DIRECTORY_SEPARATOR . 'operators';

    /**
     * @var string
     */
    public $operatorsNamespace = 'mvbsoft\queryManager\operators';


    /** @var OperatorAbstract[]  */
    private $_operators = [];

    /**
     * @return string[]
     */
    public static function conditions(): array
    {
        return [
            self::CONDITION_OR,
            self::CONDITION_AND,
        ];
    }

    /**
     * @return string[]
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
     * @return string[]
     */
    public static function conditionElementsTypes(): array
    {
        return [
            self::CONDITION_ELEMENT_TYPE_GROUP,
            self::CONDITION_ELEMENT_TYPE_INDIVIDUAL,
        ];
    }

    /**
     * @throws Exception
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
     * @return string[]
     */
    public function getOperatorSlugs(): array
    {
        return array_map(function(OperatorAbstract $operator){
            return $operator->slug;
        }, $this->operators);
    }

    /**
     * @param string $slug
     * @return OperatorAbstract|null
     */
    public function getOperatorBySlug(string $slug): ?OperatorAbstract
    {
        return current(array_filter($this->operators, function (OperatorAbstract $operator) use ($slug) {
            return $operator->slug == $slug;
        }));
    }

    public function groupModal(): DynamicModel
    {
        $model = new DynamicModel(['id', 'column', 'type', 'operator', 'value']);

        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition', 'type', 'name'], 'required');
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['type'], 'in', ['range' => self::conditionElementsTypes()]);
        $model->addRule(['name'], 'string', ['min' => 1, 'max' => 64]);

        //Max array size 2 megabytes
        $model->addRule(['elements'], ArrayValidator::class, ['maxSizeInBytes' => 1024 * 1024 * 2, 'skipOnEmpty' => false]);

        return $model;
    }

    public function individualModel(): DynamicModel
    {
        $model = new DynamicModel(['id', 'condition', 'column', 'type', 'operator', 'value']);

        $model->addRule(['condition', 'column', 'type', 'operator', 'value'], 'required');
        $model->addRule(['id'], 'string', ['min' => 6, 'max' => 12]);
        $model->addRule(['condition'], 'in', ['range' => self::conditions()]);
        $model->addRule(['column'], 'string', ['min' => 1, 'max' => 255]);
        $model->addRule(['type'], 'in', ['range' => self::conditionElementsTypes()]);
        $model->addRule(['operator'], 'in', ['range' => $this->operatorSlugs]);

        $model->addRule(['value'], function ($attribute) use ($model) {
            $value = $model->$attribute;

            if(!is_string($value) && !is_integer($value) && !is_array($value)){
                $model->addError($attribute, 'Value can be string or integer or array');
            }
        });

        //Max array size 100 kilobytes
        $model->addRule(['value'], ArrayValidator::class, ['maxSizeInBytes' => 100, 'when' => function($model){
            return is_array($model->value);
        }]);

        return $model;
    }

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
     * @param array $queryElements
     * @param string $conditionType
     * @param array $arrayData
     * @return PostgresqlQuery|MongodbQuery
     */
    public function generateCondition(array $queryElements, string $conditionType, array $arrayData = [])
    {
        if($conditionType == self::CONDITION_TYPE_MONGODB){
            $query = new MongodbQuery();
        }
        else{
            $query = new PostgresqlQuery();
        }

        foreach ($queryElements as $element){
            $type           = $element['type'];
            $condition      = $element['condition'];

            if($type == self::CONDITION_ELEMENT_TYPE_INDIVIDUAL){
                $column      = $element['column'];
                $operator    = $element['operator'];
                $searchValue = $element['value'];

                $where = $this->generateWhere($operator, $conditionType, $searchValue, $column, $arrayData[$column] ?? null);

                if($condition == self::CONDITION_OR && !is_null($where)){
                    $query->orWhere($where);
                }

                if($condition == self::CONDITION_AND && !is_null($where)){
                    $query->andWhere($where);
                }
            }

            if($type == self::CONDITION_ELEMENT_TYPE_GROUP){
                $groupElements = $element['elements'];

                $groupQuery = $this->generateCondition($groupElements, $conditionType, $arrayData);

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

    public function phpQuery(array $queryElements, array $arrayData): bool
    {
        $queryBuilder = new \yii\db\QueryBuilder(Yii::$app->db);

        $query = $this->generateCondition($queryElements,  self::CONDITION_TYPE_PHP,  $arrayData);

        $params = [];

        $buildWhere = $queryBuilder->buildWhere($query->where, $params);

        $buildWhere = str_replace(["WHERE ", "(1)", "(0)", "AND", "OR"], ["", 1, 0, "&&", "||"], $buildWhere);

        $result = false;

        if(!empty($buildWhere)){
            eval('$result = boolval('. $buildWhere .');');
        }

        return $result;
    }

    public function mongodbQuery(array $queryElements): MongodbQuery
    {
        return $this->generateCondition($queryElements,  self::CONDITION_TYPE_MONGODB);
    }

    public function postgresqlQuery(array $queryElements): PostgresqlQuery
    {
        return $this->generateCondition($queryElements,  self::CONDITION_TYPE_POSTGRESQL);
    }

    /**
     * @param string $operatorSlug - operator slug, if you cannot find operator object by slug, returns null
     * @param string $conditionType - condition type (php, mongodb, postgresql)
     * @param mixed $searchValue - the value that we try to find
     * @param string $column - the column where we try to find $searchValue
     * @param mixed|null $value - value (using only with php condition) the data for current column from php array
     * @return int|array|null - int (0 or 1) if using php condition. array - thi is condition using in yii2 query builder where() method. null if cannot generate result
     */
    public function generateWhere(string $operatorSlug, string $conditionType, $searchValue, string $column, $value = null) {
        $operator = $this->getOperatorBySlug($operatorSlug);

        if(!$operator instanceof OperatorAbstract){
            return null;
        }

        if($conditionType == self::CONDITION_TYPE_PHP){
            return intval($operator::phpCondition($searchValue, $column, $value));
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