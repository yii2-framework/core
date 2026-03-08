<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\jquery\validators\RegularExpressionValidatorJqueryClientScript;
use yii\validators\client\ClientValidatorScriptInterface;
use yii\web\JsExpression;

use function is_array;

/**
 * RegularExpressionValidator validates that the attribute value matches the specified [[pattern]].
 *
 * If the [[not]] property is set true, the validator will ensure the attribute value do NOT match the [[pattern]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RegularExpressionValidator extends Validator
{
    /**
     * @var string the regular expression to be matched with
     */
    public $pattern;
    /**
     * @var bool whether to invert the validation logic. Defaults to false. If set to true,
     * the regular expression defined via [[pattern]] should NOT match the attribute value.
     */
    public $not = false;
    /**
     * @var array|ClientValidatorScriptInterface|null the client-side validation script implementation.
     */
    public $clientScript = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if ($this->pattern === null) {
            throw new InvalidConfigException('The "pattern" property must be set.');
        }

        $this->message ??= Yii::t(
            'yii',
            '{attribute} is invalid.',
        );

        if ((Yii::$app->useJquery ?? false) && !$this->clientScript instanceof ClientValidatorScriptInterface) {
            $this->clientScript ??= ['class' => RegularExpressionValidatorJqueryClientScript::class];
            $this->clientScript = Yii::createObject($this->clientScript);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        $valid = !is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
            || $this->not && !preg_match($this->pattern, $value));

        return $valid ? null : [$this->message, []];
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
