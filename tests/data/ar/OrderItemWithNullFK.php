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
 * Class OrderItem.
 *
 * @property int $order_id
 * @property int $item_id
 * @property int $quantity
 * @property string $subtotal
 */
class OrderItemWithNullFK extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'order_item_with_null_fk';
    }

    public function getOrderItem(): ActiveQuery
    {
        return $this->hasOne(OrderItem::class, ['order_id' => 'order_id', 'item_id' => 'item_id']);
    }
}
