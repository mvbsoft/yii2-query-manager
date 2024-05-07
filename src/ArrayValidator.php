<?php

namespace mvbsoft\queryManager;

use yii\base\Model;
use yii\validators\Validator;

/**
 * ArrayValidator validates the array.
 *
 * The attribute value must be an array and its serialized size should not exceed the specified limit.
 */
class ArrayValidator extends Validator
{
    /**
     * @var int the maximum size of the array in bytes.
     */
    public $maxSizeInBytes = 1024;

    /**
     * @param Model $model
     * @param string $attribute
     * @return void
     */
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_array($value)) {
            $model->addError($attribute, 'Attribute must be an array.');
            return;
        }

        $arraySizeInBytes = mb_strlen(serialize($value), '8bit');

        if ($arraySizeInBytes > $this->maxSizeInBytes) {
            $model->addError($attribute, 'Array size should not exceed ' . $this->maxSizeInBytes . ' bytes.');
        }
    }
}
