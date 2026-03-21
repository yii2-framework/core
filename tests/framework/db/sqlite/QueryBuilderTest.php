<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yiiunit\base\db\BaseQueryBuilder;
use yiiunit\framework\db\sqlite\providers\QueryBuilderProvider;

/**
 * Unit test for {@see \yii\db\sqlite\QueryBuilder} with SQLite driver.
 *
 * {@see QueryBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('query-builder')]
final class QueryBuilderTest extends BaseQueryBuilder
{
    protected $driverName = 'sqlite';

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

    public function testCommentColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\sqlite\QueryBuilder::addCommentOnColumn is not supported by SQLite.',
        );

        parent::testCommentColumn();
    }

    public function testCommentTable(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'yii\db\sqlite\QueryBuilder::addCommentOnTable is not supported by SQLite.',
        );

        parent::testCommentTable();
    }

    public function testRenameTable(): void
    {
        $sql = $this->getDb()->getQueryBuilder()->renameTable('table_from', 'table_to');

        self::assertSame(
            <<<SQL
            ALTER TABLE `table_from` RENAME TO `table_to`
            SQL,
            $sql,
            'RENAME TABLE should generate correct SQL.',
        );
    }

    public function testResetSequence(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();

        $expected = <<<SQL
        UPDATE sqlite_sequence SET seq='5' WHERE name='item'
        SQL;

        $sql = $qb->resetSequence('item');

        self::assertSame(
            $expected,
            $sql,
            'Reset sequence without value should use current max value.',
        );

        $expected = <<<SQL
        UPDATE sqlite_sequence SET seq='3' WHERE name='item'
        SQL;

        $sql = $qb->resetSequence('item', 4);

        self::assertSame(
            $expected,
            $sql,
            "Reset sequence with explicit value should use 'value - 1'.",
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'primaryKeysProvider')]
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addPrimaryKey|dropPrimaryKey) is not supported by SQLite\.$/',
        );

        parent::testAddDropPrimaryKey($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'foreignKeysProvider')]
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addForeignKey|dropForeignKey) is not supported by SQLite\.$/',
        );

        parent::testAddDropForeignKey($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'uniquesProvider')]
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addUnique|dropUnique) is not supported by SQLite\.$/',
        );

        parent::testAddDropUnique($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'checksProvider')]
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addCheck|dropCheck) is not supported by SQLite\.$/',
        );

        parent::testAddDropCheck($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'defaultValuesProvider')]
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^.*::(addDefaultValue|dropDefaultValue) is not supported by SQLite\.$/',
        );

        parent::testAddDropDefaultValue($sql, $builder);
    }
}
