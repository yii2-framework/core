<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yiiunit\base\db\BaseQueryBuilder;
use yiiunit\framework\db\pgsql\providers\QueryBuilderProvider;

/**
 * Unit test for {@see \yii\db\pgsql\QueryBuilder} with PostgreSQL driver.
 *
 * {@see QueryBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('pgsql')]
#[Group('query-builder')]
final class QueryBuilderTest extends BaseQueryBuilder
{
    public $driverName = 'pgsql';

    #[DataProviderExternal(QueryBuilderProvider::class, 'conditionProvider')]
    public function testBuildCondition($condition, $expected, $expectedParams): void
    {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'indexesProvider')]
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        parent::testCreateDropIndex($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsertProvider')]
    public function testBatchInsert($table, $columns, $value, $expected, $replaceQuotes = true): void
    {
        parent::testBatchInsert($table, $columns, $value, $expected, $replaceQuotes);
    }

    #[Depends('testInitFixtures')]
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertProvider')]
    public function testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams): void
    {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'updateProvider')]
    public function testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams): void
    {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'alterColumnProvider')]
    public function testAlterColumn(string $table, string $column, string|\Closure $type, string $expected): void
    {
        $qb = $this->getConnection()->queryBuilder;

        if ($type instanceof Closure) {
            $type = $type($this);
        }

        self::assertSame(
            $expected,
            $qb->alterColumn($table, $column, $type),
            'ALTER COLUMN SQL does not match expected output.',
        );
    }

    public function testCommentColumn(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        COMMENT ON COLUMN [[comment]].[[text]] IS 'This is my column.'
        SQL;

        $sql = $qb->addCommentOnColumn('comment', 'text', 'This is my column.');

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Add column comment should generate correct SQL.',
        );

        $expected = <<<SQL
        COMMENT ON COLUMN [[comment]].[[text]] IS NULL
        SQL;

        $sql = $qb->dropCommentFromColumn('comment', 'text');

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Drop column comment should generate correct SQL.',
        );
    }

    public function testCommentTable(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        COMMENT ON TABLE [[comment]] IS 'This is my table.'
        SQL;

        $sql = $qb->addCommentOnTable('comment', 'This is my table.');

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Add table comment should generate correct SQL.',
        );

        $expected = <<<SQL
        COMMENT ON TABLE [[comment]] IS NULL
        SQL;

        $sql = $qb->dropCommentFromTable('comment');

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Drop table comment should generate correct SQL.',
        );
    }

    public function testResetSequence(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        SELECT SETVAL('"item_id_seq"',(SELECT COALESCE(MAX("id"),0) FROM "item")+1,false)
        SQL;

        $sql = $qb->resetSequence('item');

        self::assertSame(
            $expected,
            $sql,
            "Reset sequence without value should use 'MAX(id) + 1'.",
        );

        $expected = <<<SQL
        SELECT SETVAL('"item_id_seq"',4,false)
        SQL;

        $sql = $qb->resetSequence('item', 4);

        self::assertSame(
            $expected,
            $sql,
            'Reset sequence with explicit value should use provided value.',
        );
    }

    public function testResetSequencePostgres12(): void
    {
        $config = $this->database;

        unset($config['fixture']);

        $db = $this->prepareDatabase($config, realpath(__DIR__ . '/../../../data') . '/postgres12.sql');

        $qb = $db->queryBuilder;

        $expected = <<<SQL
        SELECT SETVAL('"item_12_id_seq"',(SELECT COALESCE(MAX("id"),0) FROM "item_12")+1,false)
        SQL;

        $sql = $qb->resetSequence('item_12');

        self::assertSame(
            $expected,
            $sql,
            "PostgreSQL '12' identity column reset should use 'MAX(id) + 1'.",
        );

        $expected = <<<SQL
        SELECT SETVAL('"item_12_id_seq"',4,false)
        SQL;

        $sql = $qb->resetSequence('item_12', 4);

        self::assertSame(
            $expected,
            $sql,
            "PostgreSQL '12' identity column reset with explicit value should use provided value.",
        );
    }

    public function testDropIndex(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        DROP INDEX "index"
        SQL;

        $sql = $qb->dropIndex('index', '{{table}}');

        self::assertSame(
            $expected,
            $sql,
            'Drop index without schema should generate correct SQL.',
        );

        $expected = <<<SQL
        DROP INDEX "schema"."index"
        SQL;

        $sql = $qb->dropIndex('index', '{{schema.table}}');

        self::assertSame(
            $expected,
            $sql,
            'Drop index with schema should generate correct SQL.',
        );

        $expected = <<<SQL
        DROP INDEX "schema"."index"
        SQL;

        $sql = $qb->dropIndex('schema.index', '{{schema2.table}}');

        self::assertSame(
            $expected,
            $sql,
            'Drop index with explicit schema should generate correct SQL.',
        );

        $expected = <<<SQL
        DROP INDEX "schema"."index"
        SQL;

        $sql = $qb->dropIndex('index', '{{schema.%table}}');

        self::assertSame(
            $expected,
            $sql,
            'Drop index with schema prefix should generate correct SQL.',
        );

        $expected = <<<SQL
        DROP INDEX {{%schema.index}}
        SQL;

        $sql = $qb->dropIndex('index', '{{%schema.table}}');

        self::assertSame(
            $expected,
            $sql,
            'Drop index with table prefix should generate correct SQL.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'defaultValuesProvider')]
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^pgsql does not support (adding|dropping) default value constraints\.$/',
        );

        parent::testAddDropDefaultValue($sql, $builder);
    }
}
