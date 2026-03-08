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
use yii\helpers\Html;
use yii\helpers\Json;
use yii\validators\client\ClientValidatorScriptInterface;
use yii\validators\IpValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;
use yii\web\JsExpression;
use yii\web\View;

/**
 * jQuery client-side script for [[IpValidator]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class IpValidatorJqueryClientScript extends BaseObject implements ClientValidatorScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(Validator $validator, Model $model, string $attribute): array
    {
        /** @var IpValidator $validator */
        $messages = [
            'ipv6NotAllowed' => $validator->ipv6NotAllowed,
            'ipv4NotAllowed' => $validator->ipv4NotAllowed,
            'message' => $validator->message,
            'noSubnet' => $validator->noSubnet,
            'hasSubnet' => $validator->hasSubnet,
        ];
        foreach ($messages as &$message) {
            $message = $validator->getFormattedClientMessage(
                $message,
                ['attribute' => $model->getAttributeLabel($attribute)],
            );
        }

        $options = [
            'ipv4Pattern' => new JsExpression(
                Html::escapeJsRegularExpression($validator->ipv4Pattern),
            ),
            'ipv6Pattern' => new JsExpression(
                Html::escapeJsRegularExpression($validator->ipv6Pattern),
            ),
            'messages' => $messages,
            'ipv4' => $validator->ipv4,
            'ipv6' => $validator->ipv6,
            'ipParsePattern' => new JsExpression(
                Html::escapeJsRegularExpression($this->getIpParsePattern($validator)),
            ),
            'negation' => $validator->negation,
            'subnet' => $validator->subnet,
        ];

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

        return 'yii.validation.ip(value, messages, ' . Json::htmlEncode($options) . ');';
    }

    /**
     * Returns the Regexp pattern for initial IP address parsing.
     *
     * @param IpValidator $validator
     * @return string
     */
    private function getIpParsePattern(IpValidator $validator): string
    {
        return '/^(' . preg_quote(IpValidator::NEGATION_CHAR, '/') . '?)(.+?)(\/(\d+))?$/';
    }
}
