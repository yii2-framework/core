<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\jquery\validators;

use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\Json;
use yii\validators\client\ClientValidatorScriptInterface;
use yii\validators\NumberValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\JsExpression;
use yii\web\View;

/**
 * jQuery client-side script for [[NumberValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class NumberValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var NumberValidator $validator */
        $label = $model->getAttributeLabel($attribute);

        $options = [
            'pattern' => new JsExpression(
                $validator->integerOnly ? $validator->integerPattern : $validator->numberPattern,
            ),
            'message' => $validator->getFormattedClientMessage(
                $validator->message,
                ['attribute' => $label],
            ),
        ];

        if ($validator->min !== null) {
            $options['min'] = $validator->min;
            $options['tooSmall'] = $validator->getFormattedClientMessage(
                $validator->tooSmall,
                [
                    'attribute' => $label,
                    'min' => $validator->min,
                ],
            );
        }

        if ($validator->max !== null) {
            $options['max'] = $validator->max;
            $options['tooBig'] = $validator->getFormattedClientMessage(
                $validator->tooBig,
                [
                    'attribute' => $label,
                    'max' => $validator->max,
                ],
            );
        }

        if ($validator->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Validator $validator, Model $model, string $attribute, View $view): string|null
    {
        ValidationAsset::register($view);

        $options = $this->getClientOptions($validator, $model, $attribute);

        return 'yii.validation.number(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}
