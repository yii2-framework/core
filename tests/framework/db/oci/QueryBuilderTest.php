<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yii\db\Query;
use yiiunit\base\db\BaseQueryBuilder;
use yiiunit\framework\db\oci\providers\QueryBuilderProvider;

/**
 * Unit test for {@see \yii\db\oci\QueryBuilder} with Oracle driver.
 *
 * {@see QueryBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
#[Group('query-builder')]
final class QueryBuilderTest extends BaseQueryBuilder
{
    public $driverName = 'oci';

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

    public function testBuildOrderByAndLimitWithOffsetAndLimit(): void
    {
        $query = new Query();

        $query->select('id')->from('example')->limit(10)->offset(5);

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example" ORDER BY (SELECT NULL) OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $actualQuerySql,
            'OFFSET and LIMIT should generate OFFSET x ROWS FETCH NEXT y ROWS ONLY.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'OFFSET/LIMIT query should have no bound parameters.',
        );
    }

    public function testBuildOrderByAndLimitWithLimitOnly(): void
    {
        $query = new Query();

        $query->select('id')->from('example')->limit(10);

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example" ORDER BY (SELECT NULL) OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $actualQuerySql,
            'LIMIT without OFFSET should generate OFFSET 0 ROWS FETCH NEXT y ROWS ONLY.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'LIMIT-only query should have no bound parameters.',
        );
    }

    public function testBuildOrderByAndLimitWithOffsetOnly(): void
    {
        $query = new Query();

        $query->select('id')->from('example')->offset(10);

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example" ORDER BY (SELECT NULL) OFFSET 10 ROWS
            SQL,
            $actualQuerySql,
            'OFFSET without LIMIT should generate OFFSET x ROWS without FETCH clause.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'OFFSET-only query should have no bound parameters.',
        );
    }

    public function testBuildOrderByAndLimitWithoutOffsetAndLimit(): void
    {
        $query = new Query();

        $query->select('id')->from('example');

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example"
            SQL,
            $actualQuerySql,
            'Query without OFFSET/LIMIT should not contain OFFSET or FETCH clauses.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'Query without OFFSET/LIMIT should have no bound parameters.',
        );
    }

    public function testBuildOrderByAndLimitWithExplicitOrderBy(): void
    {
        $query = new Query();

        $query->select('id')->from('example')->orderBy('id')->limit(10)->offset(5);

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example" ORDER BY "id" OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
            SQL,
            $actualQuerySql,
            'Explicit ORDER BY should be used instead of ORDER BY (SELECT NULL).',
        );
        self::assertEmpty(
            $actualQueryParams,
            'Query with explicit ORDER BY should have no bound parameters.',
        );
    }

    public function testBuildOrderByAndLimitWithOrderByWithoutPagination(): void
    {
        $query = new Query();

        $query->select('id')->from('example')->orderBy('id');

        [$actualQuerySql, $actualQueryParams] = $this->getDb()->getQueryBuilder()->build($query);

        self::assertSame(
            <<<SQL
            SELECT "id" FROM "example" ORDER BY "id"
            SQL,
            $actualQuerySql,
            'ORDER BY without OFFSET/LIMIT should not contain OFFSET or FETCH clauses.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'ORDER BY without pagination should have no bound parameters.',
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
        COMMENT ON COLUMN [[comment]].[[text]] IS ''
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
        COMMENT ON TABLE [[comment]] IS ''
        SQL;

        $sql = $qb->dropCommentFromTable('comment');

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Drop table comment should generate correct SQL.',
        );
    }

    public function testExecuteResetSequence(): void
    {
        $db = $this->getDb();

        $qb = $db->getQueryBuilder();

        $sqlResult = <<<SQL
        SELECT last_number FROM user_sequences WHERE sequence_name = 'item_SEQ'
        SQL;

        $qb->executeResetSequence('item');
        $result = $db->createCommand($sqlResult)->queryScalar();

        self::assertSame(
            '6',
            $result,
            'Reset sequence without value should set to next auto-increment value.',
        );

        $qb->executeResetSequence('item', 4);
        $result = $db->createCommand($sqlResult)->queryScalar();

        self::assertSame(
            '4',
            $result,
            'Reset sequence with explicit value should set to provided value.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsertProvider')]
    public function testBatchInsert($table, $columns, $value, $expected, $replaceQuotes = true): void
    {
        parent::testBatchInsert($table, $columns, $value, $expected, $replaceQuotes);
    }

    /**
     * Dummy test to speed up QB's tests which rely on DB schema
     */
    public function testInitFixtures(): void
    {
        self::assertInstanceOf('yii\db\QueryBuilder', $this->getConnection(true, true)->getQueryBuilder());
    }

    #[Depends('testInitFixtures')]
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertProvider')]
    public function testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams): void
    {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'defaultValuesProvider')]
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessageMatches(
            '/^oci does not support (adding|dropping) default value constraints\.$/',
        );

        parent::testAddDropDefaultValue($sql, $builder);
    }
}
