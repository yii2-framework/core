<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\ar;

/**
 * Class CustomerWithInitParams.
 *
 * @property int $id
 * @property int $status
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class CustomerWithInitParams extends ActiveRecord
{
    public const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'customer';
    }

    /**
     * {@inheritdoc}
     * @return CustomerWithInitParamsQuery
     */
    public static function find()
    {
        return new CustomerWithInitParamsQuery(static::class);
    }
}
