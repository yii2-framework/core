<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yiiunit\base\db\BaseQueryBuilder;
use yiiunit\framework\db\mysql\providers\QueryBuilderProvider;

/**
 * Unit test for {@see \yii\db\mysql\QueryBuilder} with MySQL driver.
 *
 * {@see QueryBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('query-builder')]
final class QueryBuilderTest extends BaseQueryBuilder
{
    protected $driverName = 'mysql';

    #[DataProviderExternal(QueryBuilderProvider::class, 'primaryKeysProvider')]
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        parent::testAddDropPrimaryKey($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'foreignKeysProvider')]
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        parent::testAddDropForeignKey($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'indexesProvider')]
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        parent::testCreateDropIndex($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'uniquesProvider')]
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        parent::testAddDropUnique($sql, $builder);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'conditionProvider')]
    public function testBuildCondition($condition, $expected, $expectedParams): void
    {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'updateProvider')]
    public function testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams): void
    {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    #[Depends('testInitFixtures')]
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertProvider')]
    public function testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams): void
    {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    public function testResetSequence(): void
    {
        $qb = $this->getDb()->getQueryBuilder();

        $expected = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=6
        SQL;

        $sql = $qb->resetSequence('item');

        self::assertSame(
            $expected,
            $sql,
            'Reset sequence without value should use next auto-increment value.',
        );

        $expected = <<<SQL
        ALTER TABLE `item` AUTO_INCREMENT=4
        SQL;

        $sql = $qb->resetSequence('item', 4);

        self::assertSame(
            $expected,
            $sql,
            'Reset sequence with explicit value should use provided value.',
        );
    }

    public function testIssue17449(): void
    {
        $db = $this->getConnection(false);

        $pdo = $db->pdo;

        $pdo->exec('DROP TABLE IF EXISTS `issue_17449`');

        $tableQuery = <<<SQL
        CREATE TABLE `issue_17449` (
            `test_column` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'some comment' CHECK (json_valid(`test_column`))
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1
        SQL;

        $db->createCommand($tableQuery)->execute();

        $actual = $db->createCommand()->addCommentOnColumn('issue_17449', 'test_column', 'Some comment')->rawSql;

        $checkPos = stripos($actual, 'check');

        if ($checkPos === false) {
            $this->markTestSkipped("The used MySql-Server removed or moved the CHECK from the column line, so the original bug doesn't affect it");
        }

        $commentPos = stripos($actual, 'comment');

        self::assertNotFalse(
            $commentPos,
            'COMMENT keyword should be present in ALTER statement.',
        );
        self::assertLessThan(
            $checkPos,
            $commentPos,
            'COMMENT should appear before CHECK in ALTER statement.',
        );
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/14663
     */
    public function testInsertInteger(): void
    {
        $db = $this->getDb();

        $command = $db->createCommand();

        // int value should not be converted to string, when column is `int`
        $sql = $command->insert('{{type}}', ['int_col' => 22])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `type` (`int_col`) VALUES (22)
            SQL,
            $sql,
            'INT column value should remain integer.',
        );

        // int value should not be converted to string, when column is `int unsigned`
        $sql = $command->insert('{{type}}', ['int_col3' => 22])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `type` (`int_col3`) VALUES (22)
            SQL,
            $sql,
            'INT UNSIGNED column value should remain integer.',
        );

        // int value should not be converted to string, when column is `bigint unsigned`
        $sql = $command->insert('{{type}}', ['bigint_col' => 22])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `type` (`bigint_col`) VALUES (22)
            SQL,
            $sql,
            'BIGINT UNSIGNED column value should remain integer.',
        );

        // string value should not be converted
        $sql = $command->insert('{{type}}', ['bigint_col' => '1000000000000'])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `type` (`bigint_col`) VALUES ('1000000000000')
            SQL,
            $sql,
            'String value should remain as string.',
        );
    }

    /**
     * Test for issue https://github.com/yiisoft/yii2/issues/15500
     */
    public function testDefaultValues(): void
    {
        $db = $this->getDb();

        $command = $db->createCommand();

        // primary key columns should have NULL as value
        $sql = $command->insert('null_values', [])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `null_values` (`id`) VALUES (NULL)
            SQL,
            $sql,
            'Primary key column should use NULL for empty insert.',
        );

        // non-primary key columns should have DEFAULT as value
        $sql = $command->insert('negative_default_values', [])->getRawSql();

        self::assertSame(
            <<<SQL
            INSERT INTO `negative_default_values` (`tinyint_col`) VALUES (DEFAULT)
            SQL,
            $sql,
            'Non-primary key column should use DEFAULT for empty insert.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'defaultValuesProvider')]
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^mysql does not support (adding|dropping) default value constraints\.$/',
        );

        parent::testAddDropDefaultValue($sql, $builder);
    }
}
