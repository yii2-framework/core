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
use yii\validators\BooleanValidator;
use yii\validators\client\ClientValidatorScriptInterface;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\View;

/**
 * jQuery client-side script for [[BooleanValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class BooleanValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var BooleanValidator $validator */
        $options = [
            'trueValue' => $validator->trueValue,
            'falseValue' => $validator->falseValue,
            'message' => $validator->getFormattedClientMessage(
                $validator->message,
                [
                    'attribute' => $model->getAttributeLabel($attribute),
                    'true' => $validator->trueValue === true ? 'true' : $validator->trueValue,
                    'false' => $validator->falseValue === false ? 'false' : $validator->falseValue,
                ],
            ),
        ];

        if ($validator->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        if ($validator->strict) {
            $options['strict'] = 1;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Validator $validator, Model $model, string $attribute, View $view): string|null
    {
        /** @var BooleanValidator $validator */
        ValidationAsset::register($view);

        $options = $this->getClientOptions($validator, $model, $attribute);

        return 'yii.validation.boolean(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}
