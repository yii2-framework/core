<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\ar;

use yii\db\ActiveQuery;

/**
 * @extends ActiveQuery<CustomerWithInitParams>
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class CustomerWithInitParamsQuery extends ActiveQuery
{
    public function init(): void
    {
        parent::init();

        $this->where('[[status]] = :status', [':status' => CustomerWithInitParams::STATUS_ACTIVE]);
    }
}
