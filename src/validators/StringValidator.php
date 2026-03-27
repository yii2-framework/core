<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\validators\client\ClientValidatorScriptInterface;

use function is_array;
use function is_scalar;
use function is_string;

/**
 * StringValidator validates that the attribute value is of certain length.
 *
 * Note, this validator should only be used with string-typed attributes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class StringValidator extends Validator
{
    /**
     * @var int|array|null Specifies the length limit of the value to be validated.
     *
     * This can be specified in one of the following forms:
     *
     * - an integer: The exact length that the value should be of;
     * - an array of one element: The minimum length that the value should be of. For example, `[8]`.
     *   This will overwrite [[min]].
     * - an array of two elements: The minimum and maximum lengths that the value should be of.
     *   For example, `[8, 128]`. This will overwrite both [[min]] and [[max]].
     * @see tooShort for the customized message for a too short string.
     * @see tooLong for the customized message for a too long string.
     * @see notEqual for the customized message for a string that does not match desired length.
     */
    public $length;
    /**
     * @var int|null Maximum length. If not set, it means no maximum length limit.
     *
     * @see tooLong for the customized message for a too long string.
     */
    public $max;
    /**
     * @var int|null Minimum length. If not set, it means no minimum length limit.
     *
     * @see tooShort for the customized message for a too short string.
     */
    public $min;
    /**
     * @var string user-defined error message used when the value is not a string.
     */
    public $message;
    /**
     * @var string user-defined error message used when the length of the value is smaller than [[min]].
     */
    public $tooShort;
    /**
     * @var string user-defined error message used when the length of the value is greater than [[max]].
     */
    public $tooLong;
    /**
     * @var string user-defined error message used when the length of the value is not equal to [[length]].
     */
    public $notEqual;
    /**
     * @var string|null The encoding of the string value to be validated (e.g. 'UTF-8').
     *
     * If this property is not set, [[\yii\base\Application::charset]] will be used.
     */
    public $encoding;
    /**
     * @var boolean Whether to require the value to be a string data type.
     *
     * If false any scalar value will be treated as it's string equivalent.
     * @since 2.0.33
     */
    public $strict = true;
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

        if (is_array($this->length)) {
            if (isset($this->length[0])) {
                $this->min = $this->length[0];
            }

            if (isset($this->length[1])) {
                $this->max = $this->length[1];
            }

            $this->length = null;
        }

        $this->encoding ??= Yii::$app ? Yii::$app->charset : 'UTF-8';
        $this->message ??= Yii::t(
            'yii',
            '{attribute} must be a string.',
        );

        if ($this->min !== null) {
            $this->tooShort ??= Yii::t(
                'yii',
                '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
            );
        }

        if ($this->max !== null) {
            $this->tooLong ??= Yii::t(
                'yii',
                '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
            );
        }

        if ($this->length !== null) {
            $this->notEqual ??= Yii::t(
                'yii',
                '{attribute} should contain {length, number} {length, plural, one{character} other{characters}}.',
            );
        }

        if ($this->clientScript !== null && !$this->clientScript instanceof ClientValidatorScriptInterface) {
            $this->clientScript = Yii::createObject($this->clientScript);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!$this->strict && is_scalar($value) && !is_string($value)) {
            $value = (string)$value;
        }

        if (!is_string($value)) {
            $this->addError(
                $model,
                $attribute,
                $this->message,
            );

            return;
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            $this->addError(
                $model,
                $attribute,
                $this->tooShort,
                ['min' => $this->min],
            );
        }

        if ($this->max !== null && $length > $this->max) {
            $this->addError(
                $model,
                $attribute,
                $this->tooLong,
                ['max' => $this->max],
            );
        }

        if ($this->length !== null && $length !== $this->length) {
            $this->addError(
                $model,
                $attribute,
                $this->notEqual,
                ['length' => $this->length],
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        if (!$this->strict && is_scalar($value) && !is_string($value)) {
            $value = (string)$value;
        }

        if (!is_string($value)) {
            return [
                $this->message,
                [],
            ];
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            return [
                $this->tooShort,
                ['min' => $this->min],
            ];
        }

        if ($this->max !== null && $length > $this->max) {
            return [
                $this->tooLong,
                ['max' => $this->max],
            ];
        }

        if ($this->length !== null && $length !== $this->length) {
            return [
                $this->notEqual,
                ['length' => $this->length],
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
