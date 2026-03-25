<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use yii\base\Event;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\QueryBuilder;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\data\ar\Category;
use yiiunit\data\ar\Customer;
use yiiunit\data\ar\Order;
use yiiunit\data\ar\Profile;
use yiiunit\framework\db\GetTablesAliasTestTrait;

/**
 * Class ActiveQueryTest the base class for testing ActiveQuery.
 */
abstract class BaseActiveQuery extends BaseDatabase
{
    use GetTablesAliasTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
    }

    public function testConstructor(): void
    {
        $config = [
            'on' => ['a' => 'b'],
            'joinWith' => ['dummy relation'],
        ];
        $query = new ActiveQuery(Customer::class, $config);
        $this->assertEquals($query->modelClass, Customer::class);
        $this->assertEquals($query->on, $config['on']);
        $this->assertEquals($query->joinWith, $config['joinWith']);
    }

    public function testTriggerInitEvent(): void
    {
        $where = '1==1';
        $callback = function (Event $event) use ($where) {
            $event->sender->where = $where;
        };
        Event::on(ActiveQuery::class, ActiveQuery::EVENT_INIT, $callback);
        $result = new ActiveQuery(Customer::class);
        $this->assertEquals($where, $result->where);
        Event::off(ActiveQuery::class, ActiveQuery::EVENT_INIT, $callback);
    }

    /**
     * @todo tests for internal logic of prepare()
     */
    public function testPrepare(): void
    {
        $query = new ActiveQuery(Customer::class);
        $builder = new QueryBuilder(new Connection());
        $result = $query->prepare($builder);
        $this->assertInstanceOf('yii\db\Query', $result);
    }

    public function testPopulateEmptyRows(): void
    {
        $query = new ActiveQuery(Customer::class);
        $rows = [];
        $result = $query->populate([]);
        $this->assertEquals($rows, $result);
    }

    /**
     * @todo tests for internal logic of populate()
     */
    public function testPopulateFilledRows(): void
    {
        $query = new ActiveQuery(Customer::class);
        $rows = $query->all();
        $result = $query->populate($rows);
        $this->assertEquals($rows, $result);
    }

    /**
     * @todo tests for internal logic of one()
     */
    public function testOne(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $query->one();
        $this->assertInstanceOf('yiiunit\data\ar\Customer', $result);
    }

    /**
     * @todo test internal logic of createCommand()
     */
    public function testCreateCommand(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $query->createCommand();
        $this->assertInstanceOf('yii\db\Command', $result);
    }

    /**
     * @todo tests for internal logic of queryScalar()
     */
    public function testQueryScalar(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $this->invokeMethod($query, 'queryScalar', ['name', null]);
        $this->assertEquals('user1', $result);
    }

    /**
     * @todo tests for internal logic of joinWith()
     */
    public function testJoinWith(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $query->joinWith('profile');
        $this->assertEquals([
            [['profile'], true, 'LEFT JOIN'],
        ], $result->joinWith);
    }

    /**
     * @todo tests for internal logic of innerJoinWith()
     */
    public function testInnerJoinWith(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $query->innerJoinWith('profile');
        $this->assertEquals([
            [['profile'], true, 'INNER JOIN'],
        ], $result->joinWith);
    }

    public function testBuildJoinWithRemoveDuplicateJoinByTableName(): void
    {
        $query = new ActiveQuery(Customer::class);
        $query->innerJoinWith('orders')
            ->joinWith('orders.orderItems');
        $this->invokeMethod($query, 'buildJoinWith');
        $this->assertEquals([
            [
                'INNER JOIN',
                'order',
                '{{customer}}.[[id]] = {{order}}.[[customer_id]]'
            ],
            [
                'LEFT JOIN',
                'order_item',
                '{{order}}.[[id]] = {{order_item}}.[[order_id]]'
            ],
        ], $query->join);
    }

    /**
     * @todo tests for the regex inside getQueryTableName
     */
    public function testGetQueryTableNameFromNotSet(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $this->invokeMethod($query, 'getTableNameAndAlias');
        $this->assertEquals(['customer', 'customer'], $result);
    }

    public function testGetQueryTableNameFromSet(): void
    {
        $options = ['from' => ['alias' => 'customer']];
        $query = new ActiveQuery(Customer::class, $options);
        $result = $this->invokeMethod($query, 'getTableNameAndAlias');
        $this->assertEquals(['customer', 'alias'], $result);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithUsesCorrectTableFromMultipleFromTables(): void
    {
        $orders = Order::find()
            ->from(['profile', 'order'])
            ->joinWith('customer')
            ->orderBy('customer.id DESC, order.id')
            ->all();

        self::assertCount(
            3,
            $orders,
            "'joinWith' should use the primary table 'order' for the ON clause, not 'profile'.",
        );
        self::assertSame(
            2,
            $orders[0]->id,
            "First order should belong to customer with highest 'id'.",
        );
        self::assertTrue(
            $orders[0]->isRelationPopulated('customer'),
            "Customer relation should be eagerly loaded via 'joinWith'.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithUsesCorrectAliasedTableFromMultipleFromTables(): void
    {
        $orders = Order::find()
            ->from(['profile', 'o' => 'order'])
            ->joinWith('customer')
            ->orderBy('customer.id DESC, o.id')
            ->all();

        self::assertCount(
            3,
            $orders,
            "'joinWith' should use the aliased primary table 'o' for the ON clause, not 'profile'.",
        );
        self::assertSame(
            2,
            $orders[0]->id,
            "First order should belong to customer with highest 'id'.",
        );
        self::assertTrue(
            $orders[0]->isRelationPopulated('customer'),
            "Customer relation should be eagerly loaded via 'joinWith'.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithUsesCorrectInlineAliasedTableFromMultipleFromTables(): void
    {
        $orders = Order::find()
            ->from(['profile', 'order o'])
            ->joinWith('customer')
            ->orderBy('customer.id DESC, o.id')
            ->all();

        self::assertCount(
            3,
            $orders,
            "'joinWith' should use the inline alias 'o' for the ON clause, not 'profile'.",
        );
        self::assertSame(
            2,
            $orders[0]->id,
            "First order should belong to customer with highest 'id'.",
        );
        self::assertTrue(
            $orders[0]->isRelationPopulated('customer'),
            "Customer relation should be eagerly loaded via 'joinWith'.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithUsesCorrectAsAliasedTableFromMultipleFromTables(): void
    {
        $orders = Order::find()
            ->from(['profile', 'order AS o'])
            ->joinWith('customer')
            ->orderBy('customer.id DESC, o.id')
            ->all();

        self::assertCount(
            3,
            $orders,
            "'joinWith' should use the AS-aliased primary table 'o' for the ON clause, not 'profile'.",
        );
        self::assertSame(
            2,
            $orders[0]->id,
            "First order should belong to customer with highest 'id'.",
        );
        self::assertTrue(
            $orders[0]->isRelationPopulated('customer'),
            "Customer relation should be eagerly loaded via 'joinWith'.",
        );
    }

    /**
     * Verifies the generated SQL references the model's primary table in the JOIN ON clause when multiple tables are
     * specified in `from()`.
     *
     * Mirrors the exact scenario from the original issue: the model's primary table is not the first entry in `from()`,
     * yet the ON clause must reference it — not the first table.
     *
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithMultipleFromTablesGeneratesCorrectSql(): void
    {
        $db = $this->getConnection();

        $sql = Order::find()
            ->from(['profile', 'category', 'order'])
            ->joinWith('customer')
            ->select(['order.id', 'customer.name'])
            ->where(['customer.id' => 1])
            ->orderBy('customer.name, order.id')
            ->createCommand($db)
            ->getRawSql();

        self::assertStringContainsString(
            $this->replaceQuotes('[[order]].[[customer_id]] = [[customer]].[[id]]'),
            $sql,
            "JOIN ON clause should reference 'order' (primary table), not 'profile' (first table in from).",
        );
        self::assertStringNotContainsString(
            $this->replaceQuotes('[[profile]].[[customer_id]]'),
            $sql,
            "JOIN ON clause must not reference 'profile' as the source for the join condition.",
        );
        self::assertStringNotContainsString(
            $this->replaceQuotes('[[category]].[[customer_id]]'),
            $sql,
            "JOIN ON clause must not reference 'category' as the source for the join condition.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithFallsBackToFirstTableWhenPrimaryNotInFrom(): void
    {
        $db = $this->getConnection();

        $sql = Customer::find()
            ->from(['order'])
            ->joinWith('orders')
            ->createCommand($db)
            ->getRawSql();

        self::assertStringContainsString(
            $this->replaceQuotes('[[order]].[[id]] = [[order]].[[customer_id]]'),
            $sql,
            'JOIN ON clause should fall back to the first table when the primary table is not in from.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithFallsBackToFirstAliasedTableWhenPrimaryNotInFrom(): void
    {
        $db = $this->getConnection();

        $sql = Customer::find()
            ->from(['o' => 'order'])
            ->joinWith('orders')
            ->createCommand($db)
            ->getRawSql();

        self::assertStringContainsString(
            $this->replaceQuotes('[[o]].[[id]] = [[order]].[[customer_id]]'),
            $sql,
            'JOIN ON clause should fall back to the first aliased table when the primary table is not in from.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithFallsBackToFirstInlineAliasedTableWhenPrimaryNotInFrom(): void
    {
        $db = $this->getConnection();

        $sql = Customer::find()
            ->from(['order o'])
            ->joinWith('orders')
            ->createCommand($db)
            ->getRawSql();

        self::assertStringContainsString(
            $this->replaceQuotes('[[o]].[[id]] = [[order]].[[customer_id]]'),
            $sql,
            'JOIN ON clause should fall back to the first inline-aliased table when the primary table is not in from.',
        );
    }

    /**
     * Verifies that `getTableNameAndAlias()` matches the primary table even when `tableName()` returns the default
     * `{{%table}}` format and `from()` contains the plain table name.
     *
     * @see https://github.com/yiisoft/yii2/issues/8358
     */
    public function testJoinWithUsesCorrectTableWhenPrimaryUsesDefaultFormat(): void
    {
        $oldTableName = Order::$tableName;

        Order::$tableName = '{{%order}}';

        $orders = Order::find()
            ->from(['profile', '{{%order}}'])
            ->joinWith('customer')
            ->orderBy('customer.id DESC, {{%order}}.id')
            ->all();

        self::assertCount(
            3,
            $orders,
            "'joinWith' should match '{{%order}}' in from against '{{%order}}' from tableName().",
        );
        self::assertTrue(
            $orders[0]->isRelationPopulated('customer'),
            "Customer relation should be eagerly loaded via 'joinWith'.",
        );

        Order::$tableName = $oldTableName;
    }

    public function testOnCondition(): void
    {
        $query = new ActiveQuery(Customer::class);
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->onCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnConditionOnNotSet(): void
    {
        $query = new ActiveQuery(Customer::class);
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->andOnCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnConditionOnSet(): void
    {
        $onOld = ['active' => true];
        $query = new ActiveQuery(Customer::class);
        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->andOnCondition($on, $params);
        $this->assertEquals(['and', $onOld, $on], $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnConditionOnNotSet(): void
    {
        $query = new ActiveQuery(Customer::class);
        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->orOnCondition($on, $params);
        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnConditionOnSet(): void
    {
        $onOld = ['active' => true];
        $query = new ActiveQuery(Customer::class);
        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->orOnCondition($on, $params);
        $this->assertEquals(['or', $onOld, $on], $result->on);
        $this->assertEquals($params, $result->params);
    }

    /**
     * @todo tests for internal logic of viaTable()
     */
    public function testViaTable(): void
    {
        $query = new ActiveQuery(Customer::class, ['primaryModel' => new Order()]);
        $result = $query->viaTable(Profile::class, ['id' => 'item_id']);
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertInstanceOf('yii\db\ActiveQuery', $result->via);
    }

    public function testAliasNotSet(): void
    {
        $query = new ActiveQuery(Customer::class);
        $result = $query->alias('alias');
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertEquals(['alias' => 'customer'], $result->from);
    }

    public function testAliasYetSet(): void
    {
        $aliasOld = ['old'];
        $query = new ActiveQuery(Customer::class);
        $query->from = $aliasOld;
        $result = $query->alias('alias');
        $this->assertInstanceOf('yii\db\ActiveQuery', $result);
        $this->assertEquals(['alias' => 'old'], $result->from);
    }

    protected function createQuery()
    {
        return new ActiveQuery(null);
    }

    public function testGetTableNamesNotFilledFrom(): void
    {
        $query = new ActiveQuery(Profile::class);

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{' . Profile::tableName() . '}}' => '{{' . Profile::tableName() . '}}',
        ], $tables);
    }

    public function testGetTableNamesWontFillFrom(): void
    {
        $query = new ActiveQuery(Profile::class);
        $this->assertEquals($query->from, null);
        $query->getTablesUsedInFrom();
        $this->assertEquals($query->from, null);
    }

    /**
     * https://github.com/yiisoft/yii2/issues/5341
     *
     * Issue:     Plan     1 -- * Account * -- * User
     * Our Tests: Category 1 -- * Item    * -- * Order
     */
    public function testDeeplyNestedTableRelationWith(): void
    {
        /** @var Category $category */
        $categories = Category::find()->with('orders')->indexBy('id')->all();

        $category = $categories[1];
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertEquals(2, count($orders));
        $this->assertInstanceOf(Order::class, $orders[0]);
        $this->assertInstanceOf(Order::class, $orders[1]);
        $ids = [$orders[0]->id, $orders[1]->id];
        sort($ids);
        $this->assertEquals([1, 3], $ids);

        $category = $categories[2];
        $this->assertNotNull($category);
        $orders = $category->orders;
        $this->assertEquals(1, count($orders));
        $this->assertInstanceOf(Order::class, $orders[0]);
        $this->assertEquals(2, $orders[0]->id);
    }

    /**
     * Verifies that `onCondition` with a foreign-table hash condition is filtered out during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionForeignTableHashFilteredInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithPrimaryTableCondition;

        self::assertIsArray(
            $items,
            'Lazy loading with foreign-table hash onCondition should return an array.',
        );
        self::assertNotEmpty(
            $items,
            'Foreign-table hash condition should be filtered out, returning all order items.',
        );
    }

    /**
     * Verifies that an AND compound `onCondition` with mixed safe and foreign sub-conditions keeps only the safe part
     * during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionAndCompoundMixedFilteredInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithAndConditionMixed;

        self::assertIsArray(
            $items,
            'Lazy loading with AND compound onCondition should return an array.',
        );
        // The safe sub-condition ['quantity' => 1] is kept; the foreign one is removed.
        foreach ($items as $item) {
            self::assertEquals(
                1,
                $item->quantity,
                "Order item #{$item->item_id} should have quantity=1 after filtering safe AND sub-condition.",
            );
        }
    }

    /**
     * Verifies that an OR compound `onCondition` is entirely removed when one branch references a foreign table.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionOrCompoundForeignDroppedInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithOrConditionForeign;

        self::assertIsArray(
            $items,
            'Lazy loading with OR compound containing foreign-table branch should return an array.',
        );
        // Entire OR is dropped (removing one branch would silently broaden results).
        self::assertNotEmpty(
            $items,
            'All order items should be returned when the entire OR condition is dropped.',
        );
    }

    /**
     * Verifies that a NOT `onCondition` wrapping a foreign-table condition is removed during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionNotForeignDroppedInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithNotConditionForeign;

        self::assertIsArray(
            $items,
            'Lazy loading with NOT wrapping foreign-table condition should return an array.',
        );
        self::assertNotEmpty(
            $items,
            'All order items should be returned when the NOT foreign condition is dropped.',
        );
    }

    /**
     * Verifies that an operator-format `onCondition` referencing a foreign table is removed during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionOperatorForeignFilteredInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithOperatorCondition;

        self::assertIsArray(
            $items,
            'Lazy loading with operator-format foreign-table onCondition should return an array.',
        );
        self::assertNotEmpty(
            $items,
            'Foreign-table operator condition should be filtered out, returning all order items.',
        );
    }

    /**
     * Verifies that a string `onCondition` passes through unchanged during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionStringPassesThroughInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithStringCondition;

        self::assertIsArray(
            $items,
            'Lazy loading with string onCondition should return an array.',
        );
        // String condition '1 = 1' passes through and is always true.
        self::assertNotEmpty(
            $items,
            'String condition should pass through unchanged and return order items.',
        );
    }

    /**
     * Verifies that `onCondition` is applied directly to WHERE when JOINs are present in the relation query.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionAppliedToWhereWhenJoinsPresent(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithJoinAndOnCondition;

        self::assertIsArray(
            $items,
            'Lazy loading with manual join and onCondition should return an array.',
        );
        self::assertNotEmpty(
            $items,
            'onCondition should be applied to WHERE when JOINs are present in the relation.',
        );
    }

    /**
     * Verifies that an AND compound `onCondition` with all safe sub-conditions is fully preserved during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionAndCompoundAllSafePreservedInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithAndConditionAllSafe;

        self::assertIsArray(
            $items,
            'Lazy loading with AND compound all-safe onCondition should return an array.',
        );
        // Both sub-conditions ['quantity' => 1] AND ['subtotal' => 8.0] should be applied.
        foreach ($items as $item) {
            self::assertEquals(
                1,
                $item->quantity,
                "Order item #{$item->item_id} should have quantity=1.",
            );
            self::assertEquals(
                8.0,
                 $item->subtotal,
                "Order item #{$item->item_id} should have subtotal=8.0.",
            );
        }
    }

    /**
     * Verifies that an AND compound `onCondition` where all sub-conditions reference foreign tables is fully removed
     * during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionAndCompoundAllForeignDroppedInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithAndConditionAllForeign;

        self::assertIsArray(
            $items,
            'Lazy loading with AND compound all-foreign onCondition should return an array.',
        );
        self::assertNotEmpty(
            $items,
            'All order items should be returned when the entire AND condition is dropped.',
        );
    }

    /**
     * Verifies that integer-keyed sub-conditions are individually filtered during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionIntegerKeyedFilteredInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithIntegerKeyedConditions;

        self::assertIsArray(
            $items,
            'Lazy loading with integer-keyed mixed conditions should return an array.',
        );
        // Safe sub-condition ['quantity' => 1] is kept; foreign ['order.customer_id' => 1] is removed.
        foreach ($items as $item) {
            self::assertEquals(
                1,
                 $item->quantity,
                "Order item #{$item->item_id} should have quantity=1 after filtering integer-keyed conditions.",
            );
        }
    }

    /**
     * Verifies that an operator-format `onCondition` referencing a safe column is preserved during lazy loading.
     *
     * @see https://github.com/yiisoft/yii2/issues/9168
     */
    public function testOnConditionOperatorSafePreservedInLazyLoading(): void
    {
        $order = Order::findOne(1);

        $items = $order->orderItemsWithOperatorSafeCondition;

        self::assertIsArray(
            $items,
            'Lazy loading with operator-format safe onCondition should return an array.',
        );
        // Condition ['>=', 'quantity', 1] should be preserved and filter results.
        foreach ($items as $item) {
            self::assertGreaterThanOrEqual(
                1,
                 $item->quantity,
                "Order item #{$item->item_id} should have quantity >= 1.",
            );
        }
    }
}
