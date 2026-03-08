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
use yii\validators\RequiredValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\View;

/**
 * jQuery client-side script for [[RequiredValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class RequiredValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var RequiredValidator $validator */
        $options = [];

        $options['message'] = $validator->getFormattedClientMessage(
            $validator->message,
            ['attribute' => $model->getAttributeLabel($attribute)],
        );

        if ($validator->requiredValue !== null) {
            $options['message'] = $validator->getFormattedClientMessage(
                $validator->message,
                ['requiredValue' => $validator->requiredValue],
            );
            $options['requiredValue'] = $validator->requiredValue;
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
        ValidationAsset::register($view);

        $options = $this->getClientOptions($validator, $model, $attribute);

        return 'yii.validation.required(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}
