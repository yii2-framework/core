<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\validators\client\ClientValidatorScriptInterface;

/**
 * BooleanValidator checks if the attribute value is a boolean value.
 *
 * Possible boolean values can be configured via the [[trueValue]] and [[falseValue]] properties.
 * And the comparison can be either [[strict]] or not.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BooleanValidator extends Validator
{
    /**
     * @var mixed The value representing true status. Defaults to '1'.
     */
    public $trueValue = '1';
    /**
     * @var mixed The value representing false status. Defaults to '0'.
     */
    public $falseValue = '0';
    /**
     * @var bool Whether the comparison to [[trueValue]] and [[falseValue]] is strict.
     *
     * When this is true, the attribute value and type must both match those of [[trueValue]] or [[falseValue]].
     * Defaults to false, meaning only the value needs to be matched.
     */
    public $strict = false;
    /**
     * @var array|ClientValidatorScriptInterface|false|null The client-side validation script implementation.
     *
     * `null` (default) defers resolution: when [[Application::$useJquery]] is `true`, automatically set to
     * `yii\jquery\validators\BooleanValidatorJqueryClientScript`. Set to `false` to provide no script
     * implementation while keeping the client-validation hook active. To fully disable client-side validation,
     * set [[Validator::$enableClientValidation]] to `false` instead.
     */
    public $clientScript = null;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->message ??= Yii::t(
            'yii',
            '{attribute} must be either "{true}" or "{false}".',
        );

        if ($this->clientScript === null && (Yii::$app->useJquery ?? false)) {
            $this->clientScript = ['class' => 'yii\jquery\validators\BooleanValidatorJqueryClientScript'];
        }

        if (
            $this->clientScript !== null
            && $this->clientScript !== false
            && !$this->clientScript instanceof ClientValidatorScriptInterface
        ) {
            $this->clientScript = Yii::createObject($this->clientScript);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        $valid = $value == $this->trueValue || $value == $this->falseValue;

        if ($this->strict) {
            $valid = $value === $this->trueValue || $value === $this->falseValue;
        }

        if (!$valid) {
            return [
                $this->message,
                [
                    'true' => $this->trueValue === true ? 'true' : $this->trueValue,
                    'false' => $this->falseValue === false ? 'false' : $this->falseValue,
                ],
            ];
        }

        return null;
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
