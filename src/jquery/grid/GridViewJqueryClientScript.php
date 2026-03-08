<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\jquery\grid;

use Closure;
use Yii;
use yii\base\BaseObject;
use yii\grid\GridView;
use yii\grid\GridViewAsset;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\client\ClientScriptInterface;
use yii\web\View;

/**
 * jQuery client-side script for [[GridView]].
 *
 * Registers the `yii.gridView` jQuery plugin and encodes filtering options.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class GridViewJqueryClientScript extends BaseObject implements ClientScriptInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientOptions(BaseObject $widget, array $params = []): array
    {
        /** @var GridView $widget */
        $filterUrl = $widget->filterUrl ?? Yii::$app->request->url;
        $id = $widget->filterRowOptions['id'];
        $filterSelector = "#$id input, #$id select";

        if (isset($widget->filterSelector)) {
            $additionalFilterSelector = $widget->filterSelector;

            if ($widget->filterSelector instanceof Closure) {
                $additionalFilterSelector = ($widget->filterSelector)($widget->getId(), $id);
            }

            $filterSelector .= ", {$additionalFilterSelector}";

            if ($widget->overrideFilterSelector) {
                $filterSelector = $additionalFilterSelector;
            }
        }

        return [
            'filterUrl' => Url::to($filterUrl),
            'filterSelector' => $filterSelector,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(BaseObject $widget, View $view, array $params = []): void
    {
        /** @var GridView $widget */
        GridViewAsset::register($view);

        $id = $widget->options['id'];

        $options = Json::htmlEncode(
            [
                ...$this->getClientOptions($widget),
                'filterOnFocusOut' => $widget->filterOnFocusOut,
            ],
        );
        $view->registerJs("jQuery('#$id').yiiGridView($options);");
    }
}
