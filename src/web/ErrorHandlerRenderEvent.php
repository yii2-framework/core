<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\web;

use yii\base\Event;
use Throwable;

/**
 * ErrorHandlerRenderEvent represents events triggered by [[ErrorHandler]].
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ErrorHandlerRenderEvent extends Event
{
    /**
     * Exception being rendered.
     */
    public Throwable|null $exception = null;
    /**
     * Rendered HTML output.
     * Event handlers may modify this property.
     */
    public string $output = "";
}
