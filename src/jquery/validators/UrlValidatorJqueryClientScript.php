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
use yii\validators\PunycodeAsset;
use yii\validators\UrlValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\JsExpression;
use yii\web\View;

/**
 * jQuery client-side script for [[UrlValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class UrlValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var UrlValidator $validator */
        $pattern = $validator->pattern;

        if (strpos($validator->pattern, '{schemes}') !== false) {
            $pattern = str_replace(
                '{schemes}',
                '(' . implode('|',
                $validator->validSchemes) . ')',
                $validator->pattern,
            );
        }

        $options = [
            'pattern' => new JsExpression($pattern),
            'message' => $validator->getFormattedClientMessage(
                $validator->message,
                ['attribute' => $model->getAttributeLabel($attribute)],
            ),
            'enableIDN' => $validator->enableIDN,
        ];

        if ($validator->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        if ($validator->defaultScheme !== null) {
            $options['defaultScheme'] = $validator->defaultScheme;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Validator $validator, Model $model, string $attribute, View $view): string|null
    {
        /** @var UrlValidator $validator */
        ValidationAsset::register($view);

        if ($validator->enableIDN) {
            PunycodeAsset::register($view);
        }

        $options = $this->getClientOptions($validator, $model, $attribute);

        return 'yii.validation.url(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}
