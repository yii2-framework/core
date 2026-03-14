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
final class BeforeValidateFailsModel extends Model
{
    public function beforeValidate(): bool
    {
        return false;
    }
}
