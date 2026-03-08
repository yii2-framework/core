<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\jquery\validators;

use Closure;
use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\Json;
use yii\validators\client\ClientValidatorScriptInterface;
use yii\validators\RangeValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\View;

use function call_user_func;

/**
 * jQuery client-side script for [[RangeValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class RangeValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var RangeValidator $validator */
        $range = [];

        foreach ($validator->range as $value) {
            $range[] = (string) $value;
        }

        $options = [
            'range' => $range,
            'not' => $validator->not,
            'message' => $validator->getFormattedClientMessage(
                $validator->message,
                ['attribute' => $model->getAttributeLabel($attribute)],
            ),
        ];

        if ($validator->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        if ($validator->allowArray) {
            $options['allowArray'] = 1;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Validator $validator, Model $model, string $attribute, View $view): string|null
    {
        /** @var RangeValidator $validator */
        if ($validator->range instanceof Closure) {
            $validator->range = call_user_func($validator->range, $model, $attribute);
        }

        ValidationAsset::register($view);

        $options = $this->getClientOptions($validator, $model, $attribute);

        return 'yii.validation.range(value, messages, ' . Json::htmlEncode($options) . ');';
    }
}
