<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\web\client;

use yii\base\BaseObject;
use yii\web\View;

/**
 * Defines the contract for client-side widget script strategies.
 *
 * Implementations provide JavaScript initialization code and client options for a specific JS framework (for example,
 * jQuery).
 *
 * This allows decoupling widgets from any particular client-side library.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
interface ClientScriptInterface
{
    /**
     * Returns the client-side options for the given widget.
     *
     * @param BaseObject $widget the widget instance.
     * @param array $params additional parameters.
     * @return array the client-side options.
     */
    public function getClientOptions(BaseObject $widget, array $params = []): array;

    /**
     * Registers the JavaScript code needed for the widget.
     *
     * @param BaseObject $widget the widget instance.
     * @param View $view the view object.
     * @param array $params additional parameters.
     */
    public function register(BaseObject $widget, View $view, array $params = []): void;
}
