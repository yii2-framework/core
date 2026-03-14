<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\base;

use yii\base\Component;

/**
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class SomeClass extends Component implements SomeInterface
{
    public function emitEvent(): void
    {
        $this->trigger(self::EVENT_SUPER_EVENT);
    }
}
