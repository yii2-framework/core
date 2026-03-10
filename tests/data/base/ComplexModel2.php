<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yiiunit\data\base;

use yii\base\Model;

/**
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ComplexModel2 extends Model
{
    public function rules(): array
    {
        return [
            [['id'], 'required', 'except' => ['suddenlyUnexpectedScenario']],
            [['name', 'description'], 'filter', 'filter' => 'trim'],
            [['is_disabled'], 'boolean', 'on' => ['administration']],
        ];
    }
}
