<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\jquery\widgets;

use yii\base\BaseObject;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\client\ClientScriptInterface;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\widgets\ActiveFormAsset;

/**
 * jQuery client-side script for [[ActiveForm]].
 *
 * Registers the `yii.activeForm` jQuery plugin and encodes form/field validation options.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ActiveFormJqueryClientScript extends BaseObject implements ClientScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(BaseObject $widget, array $params = []): array
    {
        /** @var ActiveForm $widget */
        $options = [
            'encodeErrorSummary' => $widget->encodeErrorSummary,
            'errorSummary' => '.' . implode(
                '.',
                preg_split(
                    '/\s+/',
                    $widget->errorSummaryCssClass,
                    -1,
                    PREG_SPLIT_NO_EMPTY,
                ),
            ),
            'validateOnSubmit' => $widget->validateOnSubmit,
            'errorCssClass' => $widget->errorCssClass,
            'successCssClass' => $widget->successCssClass,
            'validatingCssClass' => $widget->validatingCssClass,
            'ajaxParam' => $widget->ajaxParam,
            'ajaxDataType' => $widget->ajaxDataType,
            'scrollToError' => $widget->scrollToError,
            'scrollToErrorOffset' => $widget->scrollToErrorOffset,
            'validationStateOn' => $widget->validationStateOn,
        ];

        if ($widget->validationUrl !== null) {
            $options['validationUrl'] = Url::to($widget->validationUrl);
        }

        // only get the options that are different from the default ones (set in yii.activeForm.js)
        return array_diff_assoc(
            $options,
            [
                'encodeErrorSummary' => true,
                'errorSummary' => '.error-summary',
                'validateOnSubmit' => true,
                'errorCssClass' => 'has-error',
                'successCssClass' => 'has-success',
                'validatingCssClass' => 'validating',
                'ajaxParam' => 'ajax',
                'ajaxDataType' => 'json',
                'scrollToError' => true,
                'scrollToErrorOffset' => 0,
                'validationStateOn' => ActiveForm::VALIDATION_STATE_ON_CONTAINER,
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(BaseObject $widget, View $view, array $params = []): void
    {
        /** @var ActiveForm $widget */
        $id = $widget->options['id'];

        $options = Json::htmlEncode($this->getClientOptions($widget));
        $attributes = Json::htmlEncode($widget->attributes);

        ActiveFormAsset::register($view);

        $view->registerJs("jQuery('#$id').yiiActiveForm($attributes, $options);");
    }
}
