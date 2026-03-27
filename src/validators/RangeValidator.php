<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\validators;

use Closure;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\validators\client\ClientValidatorScriptInterface;

use function call_user_func;
use function is_array;

/**
 * RangeValidator validates that the attribute value is among a list of values.
 *
 * The range can be specified via the [[range]] property.
 * If the [[not]] property is set true, the validator will ensure the attribute value is NOT among the specified range.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RangeValidator extends Validator
{
    /**
     * @var array|\Traversable|\Closure A list of valid values that the attribute value should be among or an anonymous
     * function that returns such a list. The signature of the anonymous function should be as follows,
     *
     * ```
     * function($model, $attribute) {
     *     // compute range
     *     return $range;
     * }
     * ```
     */
    public $range;
    /**
     * @var bool Whether the comparison is strict (both type and value must be the same).
     */
    public $strict = false;
    /**
     * @var bool Whether to invert the validation logic. Defaults to false. If set to true, the attribute value should
     * NOT be among the list of values defined via [[range]].
     */
    public $not = false;
    /**
     * @var bool Whether to allow array type attribute.
     */
    public $allowArray = false;
    /**
     * @var array|string|ClientValidatorScriptInterface|null The client-side validation script implementation.
     *
     * When `null` (default), no client script is registered unless a bootstrap package (for example,
     * `yii2-framework/jquery`) configures one via the DI container. To fully disable client-side validation, set
     * [[Validator::$enableClientValidation]] to `false` instead.
     */
    public array|string|ClientValidatorScriptInterface|null $clientScript = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (
            !is_array($this->range)
            && !($this->range instanceof Closure)
            && !($this->range instanceof \Traversable)
        ) {
            throw new InvalidConfigException('The "range" property must be set.');
        }

        $this->message ??= Yii::t(
            'yii',
            '{attribute} is invalid.',
        );

        if ($this->clientScript !== null && !$this->clientScript instanceof ClientValidatorScriptInterface) {
            $this->clientScript = Yii::createObject($this->clientScript);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        $in = false;

        if (
            $this->allowArray
            && ($value instanceof \Traversable || is_array($value))
            && ArrayHelper::isSubset($value, $this->range, $this->strict)
        ) {
            $in = true;
        }

        if (!$in && ArrayHelper::isIn($value, $this->range, $this->strict)) {
            $in = true;
        }

        return $this->not !== $in ? null : [$this->message, []];
    }

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        if ($this->range instanceof Closure) {
            $this->range = call_user_func($this->range, $model, $attribute);
        }

        parent::validateAttribute($model, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        if ($this->clientScript instanceof ClientValidatorScriptInterface) {
            return $this->clientScript->register($this, $model, $attribute, $view);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientOptions($model, $attribute)
    {
        if ($this->clientScript instanceof ClientValidatorScriptInterface) {
            return $this->clientScript->getClientOptions($this, $model, $attribute);
        }

        return [];
    }
}
