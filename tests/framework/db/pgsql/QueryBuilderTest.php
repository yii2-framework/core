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

    public function testAlterColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', 'varchar(255)');

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN to 'varchar(255)' should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" SET NOT null
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', 'SET NOT null');

        self::assertSame(
            $expected,
            $sql,
            "SET 'NOT NULL' should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" DROP DEFAULT
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', 'DROP DEFAULT');

        self::assertSame(
            $expected,
            $sql,
            "DROP 'DEFAULT' should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" reset xyz
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', 'reset xyz');

        self::assertSame(
            $expected,
            $sql,
            "RESET should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255));

        self::assertSame(
            $expected,
            $sql,
            'ALTER COLUMN with ColumnSchemaBuilder should generate correct SQL.',
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" SET NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->notNull());

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN 'NOT NULL' with ColumnSchemaBuilder should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL, ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->check('char_length(bar) > 5'));

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN with 'CHECK' should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT '', ALTER COLUMN "bar" DROP NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue(''));

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN with empty 'DEFAULT' should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT 'AbCdE', ALTER COLUMN "bar" DROP NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(255)->defaultValue('AbCdE'));

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN with 'DEFAULT' value should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT CURRENT_TIMESTAMP, ALTER COLUMN "bar" DROP NOT NULL
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'));

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN with 'DEFAULT' expression should generate correct SQL.",
        );

        $expected = <<<SQL
        ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ALTER COLUMN "bar" DROP DEFAULT, ALTER COLUMN "bar" DROP NOT NULL, ADD UNIQUE ("bar")
        SQL;

        $sql = $qb->alterColumn('foo1', 'bar', $this->string(30)->unique());

        self::assertSame(
            $expected,
            $sql,
            "ALTER COLUMN with 'UNIQUE' should generate correct SQL.",
        );
    }

    public function testCommentColumn(): void
    {
        $qb = $this->getDb()->getQueryBuilder();

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
        $qb = $this->getDb()->getQueryBuilder();

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
        $qb = $this->getDb()->getQueryBuilder();

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

        $qb = $db->getQueryBuilder();

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
        $qb = $this->getDb()->getQueryBuilder();

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
