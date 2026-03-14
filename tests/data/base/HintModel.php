<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\base;

use yii\base\Model;

/**
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class HintModel extends Model
{
    public string|null $name = null;

    public function attributeHints(): array
    {
        return [
            'name' => 'Enter your full name',
        ];
    }
}
