<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\data\ar;

use yii\db\ActiveQuery;

/**
 * Class Order.
 *
 * @property int $id
 * @property int $customer_id
 * @property int $created_at
 * @property string $total
 *
 * @property-read Customer $customer
 * @property-read Customer $customer2
 * @property-read OrderItem[] $orderItems
 * @property-read Item[] $items
 * @property-read Item[] $expensiveItemsUsingViaWithCallable
 * @property-read Item[] $cheapItemsUsingViaWithCallable
 * @property-read Item[] $books
 * @property-read Item[] $itemsFor8
 * @property-read OrderItem[] $orderItemsWithPrimaryTableCondition
 * @property-read OrderItem[] $orderItemsWithAndConditionMixed
 * @property-read OrderItem[] $orderItemsWithOrConditionForeign
 * @property-read OrderItem[] $orderItemsWithNotConditionForeign
 * @property-read OrderItem[] $orderItemsWithOperatorCondition
 * @property-read OrderItem[] $orderItemsWithStringCondition
 * @property-read OrderItem[] $orderItemsWithJoinAndOnCondition
 * @property-read OrderItem[] $orderItemsWithAndConditionAllSafe
 * @property-read OrderItem[] $orderItemsWithOperatorSafeCondition
 * @property-read OrderItem[] $orderItemsWithAndConditionAllForeign
 * @property-read OrderItem[] $orderItemsWithIntegerKeyedConditions
 */
class Order extends ActiveRecord
{
    public static $tableName;

    public $virtualCustomerId = null;

    public static function tableName()
    {
        return static::$tableName ?: 'order';
    }

    public function getCustomer()
    {
        return $this
            ->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function getCustomerJoinedWithProfile()
    {
        return $this
            ->hasOne(Customer::class, ['id' => 'customer_id'])
            ->joinWith('profile');
    }

    public function getCustomerJoinedWithProfileIndexOrdered()
    {
        return $this
            ->hasMany(Customer::class, ['id' => 'customer_id'])
            ->joinWith('profile')->orderBy(['profile.description' => SORT_ASC])
            ->indexBy('name');
    }

    public function getCustomer2()
    {
        return $this
            ->hasOne(Customer::class, ['id' => 'customer_id'])
            ->inverseOf('orders2');
    }

    public function getOrderItems()
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getOrderItems2()
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->indexBy('item_id');
    }

    public function getOrderItems3()
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->indexBy(static fn($row): string => $row['order_id'] . '_' . $row['item_id']);
    }

    public function getOrderItemsWithNullFK()
    {
        return $this->hasMany(OrderItemWithNullFK::class, ['order_id' => 'id']);
    }

    public function getItems()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via(
                'orderItems',
                static function ($q): void {
                    // additional query configuration
                }
            )->orderBy('item.id');
    }

    public function getExpensiveItemsUsingViaWithCallable()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via(
                'orderItems',
                static function (ActiveQuery $q): void {
                    $q->where(['>=', 'subtotal', 10]);
                }
            );
    }

    public function getCheapItemsUsingViaWithCallable()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via(
                'orderItems',
                static function (ActiveQuery $q): void {
                    $q->where(['<', 'subtotal', 10]);
                }
            );
    }

    public function getItemsIndexed()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via('orderItems')->indexBy('id');
    }

    public function getItemsWithNullFK()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->viaTable('order_item_with_null_fk', ['order_id' => 'id']);
    }

    public function getItemsInOrder1()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via(
                'orderItems',
                static function (ActiveQuery $q): void {
                    $q->orderBy(['subtotal' => SORT_ASC]);
                },
            )->orderBy('name');
    }

    public function getItemsInOrder2()
    {
        return $this->hasMany(Item::class, ['id' => 'item_id'])
            ->via(
                'orderItems',
                static function (ActiveQuery $q): void {
                    $q->orderBy(['subtotal' => SORT_DESC]);
                }
            )->orderBy('name');
    }

    public function getBooks()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via('orderItems')
            ->where(['category_id' => 1]);
    }

    public function getBooksWithNullFK()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via('orderItemsWithNullFK')
            ->where(['category_id' => 1]);
    }

    public function getBooksViaTable()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->viaTable('order_item', ['order_id' => 'id'])
            ->where(['category_id' => 1]);
    }

    public function getBooksWithNullFKViaTable()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->viaTable('order_item_with_null_fk', ['order_id' => 'id'])
            ->where(['category_id' => 1]);
    }

    public function getBooks2()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->onCondition(['category_id' => 1])
            ->viaTable('order_item', ['order_id' => 'id']);
    }

    public function getBooksExplicit()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->onCondition(['category_id' => 1])
            ->viaTable('order_item', ['order_id' => 'id']);
    }

    public function getBooksExplicitA()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])->alias('bo')
            ->onCondition(['bo.category_id' => 1])
            ->viaTable('order_item', ['order_id' => 'id']);
    }

    public function getBookItems()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])->alias('books')
            ->onCondition(['books.category_id' => 1])
            ->viaTable('order_item', ['order_id' => 'id']);
    }

    public function getMovieItems()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])->alias('movies')
            ->onCondition(['movies.category_id' => 2])
            ->viaTable('order_item', ['order_id' => 'id']);
    }

    public function getLimitedItems()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->onCondition(['item.id' => [3, 5]])
            ->via('orderItems');
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->created_at = time();

            return true;
        }

        return false;
    }

    public function attributeLabels()
    {
        return [
            'customer_id' => 'Customer',
            'total' => 'Invoice Total',
        ];
    }

    public function activeAttributes()
    {
        return [
            0 => 'customer_id',
        ];
    }

    public function getQuantityOrderItems()
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id', 'quantity' => 'id']);
    }

    public function getOrderItemsFor8()
    {
        return $this
            ->hasMany(OrderItemWithNullFK::class, ['order_id' => 'id'])
            ->andOnCondition(['subtotal' => 8.0]);
    }

    public function getItemsFor8()
    {
        return $this
            ->hasMany(Item::class, ['id' => 'item_id'])
            ->via('orderItemsFor8');
    }

    public function getVirtualCustomer()
    {
        return $this
            ->hasOne(Customer::class, ['id' => 'virtualCustomerId']);
    }

    /**
     * Relation with onCondition referencing the primary table column.
     *
     * Used to test that foreign-table conditions in onCondition are filtered out during lazy/eager loading without
     * joins, preventing SQL errors from referencing tables not present in the query.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function getOrderItemsWithPrimaryTableCondition(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['order.customer_id' => 1]);
    }

    public function getOrderItemsWithAndConditionMixed(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['and', ['quantity' => 1], ['order.customer_id' => 1]]);
    }

    public function getOrderItemsWithOrConditionForeign(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['or', ['quantity' => 1], ['order.customer_id' => 1]]);
    }

    public function getOrderItemsWithNotConditionForeign(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['not', ['order.customer_id' => 2]]);
    }

    public function getOrderItemsWithOperatorCondition(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['>=', 'order.total', 100]);
    }

    public function getOrderItemsWithStringCondition(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition('1 = 1');
    }

    public function getOrderItemsWithJoinAndOnCondition(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->innerJoin('item', '{{item}}.[[id]] = {{order_item}}.[[item_id]]')
            ->onCondition(['item.category_id' => 1]);
    }

    public function getOrderItemsWithAndConditionAllSafe(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['and', ['quantity' => 1], ['subtotal' => 8.0]]);
    }

    public function getOrderItemsWithOperatorSafeCondition(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['>=', 'quantity', 1]);
    }

    public function getOrderItemsWithAndConditionAllForeign(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition(['and', ['order.customer_id' => 1], ['order.total' => 100]]);
    }

    public function getOrderItemsWithIntegerKeyedConditions(): ActiveQuery
    {
        return $this
            ->hasMany(OrderItem::class, ['order_id' => 'id'])
            ->onCondition([['quantity' => 1], ['order.customer_id' => 1]]);
    }
}
