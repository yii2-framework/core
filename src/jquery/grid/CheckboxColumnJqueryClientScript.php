<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\jquery\grid;

use yii\base\BaseObject;
use yii\grid\CheckboxColumn;
use yii\helpers\Json;
use yii\web\client\ClientScriptInterface;
use yii\web\View;

/**
 * jQuery client-side script for [[CheckboxColumn]].
 *
 * Registers the `yiiGridView('setSelectionColumn', ...)` jQuery plugin call.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class CheckboxColumnJqueryClientScript extends BaseObject implements ClientScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(BaseObject $widget, array $params = []): array
    {
        /** @var CheckboxColumn $widget */
        return [
            'name' => $widget->name,
            'class' => $widget->cssClass,
            'multiple' => $widget->multiple,
            'checkAll' => $widget->grid->showHeader ? $params['headerCheckBoxName'] ?? null : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(BaseObject $widget, View $view, array $params = []): void
    {
        /** @var CheckboxColumn $widget */
        $id = $widget->grid->options['id'];

        $options = Json::encode($this->getClientOptions($widget, $params));
        $view->registerJs("jQuery('#$id').yiiGridView('setSelectionColumn', $options);");
    }
}
