<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use Closure;
use yii\db\Expression;
use yii\db\Query;
use yii\db\QueryBuilder;
use yii\db\SchemaBuilderTrait;
use yii\helpers\ArrayHelper;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use yiiunit\base\db\providers\QueryBuilderProvider;

/**
 * Base test for {@see \yii\db\QueryBuilder} across all database drivers.
 *
 * {@see QueryBuilderProvider} for base test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseQueryBuilder extends BaseDatabase
{
    use SchemaBuilderTrait;

    public function getDb()
    {
        return $this->getConnection(false, false);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'conditionProvider')]
    public function testBuildCondition($condition, $expected, $expectedParams): void
    {
        $query = new Query();

        $query->where($condition);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        self::assertEquals(
            'SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)),
            $sql,
            'Built condition SQL does not match expected output.',
        );
        self::assertEquals(
            $expectedParams,
            $params,
            'Built condition params do not match expected params.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'filterConditionProvider')]
    public function testBuildFilterCondition($condition, $expected, $expectedParams): void
    {
        $query = new Query();

        $query->filterWhere($condition);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        self::assertSame(
            'SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)),
            $sql,
            'Built filter condition SQL does not match expected output.',
        );
        self::assertSame(
            $expectedParams,
            $params,
            'Built filter condition params do not match expected params.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildFromDataProvider')]
    public function testBuildFrom($table, $expected): void
    {
        $params = [];

        $sql = $this->getDb()->queryBuilder->buildFrom([$table], $params);

        self::assertSame(
            'FROM ' . $this->replaceQuotes($expected),
            $sql,
            'Built FROM clause does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'primaryKeysProvider')]
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        self::assertSame(
            $this->getDb()->quoteSql($sql),
            $builder($this->getDb()->queryBuilder),
            'Primary key SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'foreignKeysProvider')]
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        self::assertSame(
            $this->getDb()->quoteSql($sql),
            $builder($this->getDb()->queryBuilder),
            'Foreign key SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'indexesProvider')]
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        self::assertSame(
            $this->getDb()->quoteSql($sql),
            $builder($this->getDb()->queryBuilder),
            'Index SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'uniquesProvider')]
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        self::assertSame(
            $this->getDb()->quoteSql($sql),
            $builder($this->getDb()->queryBuilder),
            'Unique constraint SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'checksProvider')]
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        self::assertSame(
            $this->getDb()->quoteSql($sql),
            $builder($this->getDb()->queryBuilder),
            'Check constraint SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'defaultValuesProvider')]
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        self::assertSame(
            $sql,
            $builder($this->getDb()->queryBuilder),
            'Default value SQL does not match expected output.',
        );
    }

    public function testSelectSubquery(): void
    {
        $subquery = new Query();

        $subquery
            ->select('COUNT(*)')
            ->from('operations')
            ->where('account_id = accounts.id');

        $query = new Query();

        $query
            ->select('*')
            ->from('accounts')
            ->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]] FROM [[accounts]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'Select subquery SQL does not match.',
        );
        self::assertEmpty(
            $params,
            'Select subquery should have no params.',
        );
    }

    public function testComplexSelect(): void
    {
        $query = (new Query())
            ->select(
                [
                    'ID' => 't.id',
                    'gsm.username as GSM',
                    'part.Part',
                    'Part Cost' => 't.Part_Cost',
                    'st_x(location::geometry) as lon',
                    new Expression(
                        $this->replaceQuotes(
                            <<<SQL
                            case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]]
                            SQL,
                        ),
                    ),
                ],
            )
            ->from('tablename');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]] AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action' END as [[Next Action]] FROM [[tablename]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'Complex SELECT SQL does not match.',
        );
        self::assertEmpty(
            $params,
            'Complex SELECT should have no params.',
        );
    }

    public function testSelectExpression(): void
    {
        $query = new Query();

        $query
            ->select(new Expression('1 AS ab'))
            ->from('tablename');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT 1 AS ab FROM [[tablename]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'Select with single Expression does not match.',
        );
        self::assertEmpty(
            $params,
            'Select with single Expression should have no params.',
        );

        $query = new Query();

        $query
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'Select with multiple Expressions does not match.',
        );
        self::assertEmpty(
            $params,
            'Select with multiple Expressions should have no params.',
        );

        $query = new Query();

        $query
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT SUBSTR(name, 0, :len) FROM [[tablename]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'Select with Expression and params does not match.',
        );
        self::assertSame(
            [':len' => 4],
            $params,
            'Select with Expression params do not match.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/10869
     */
    public function testFromIndexHint(): void
    {
        $query = new Query();

        $query
            ->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM {{%user}} USE INDEX (primary)
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'FROM with index hint does not match.',
        );
        self::assertEmpty(
            $params,
            'FROM with index hint should have no params.',
        );

        $query = new Query();

        $query
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1) LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'FROM with FORCE/IGNORE index hints does not match.',
        );
        self::assertEmpty(
            $params,
            'FROM with FORCE/IGNORE index hints should have no params.',
        );
    }

    public function testFromSubquery(): void
    {
        // query subquery
        $subquery = new Query();

        $subquery->from('user')->where('account_id = accounts.id');

        $query = new Query();

        $query->from(['activeusers' => $subquery]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'FROM with subquery does not match.',
        );
        self::assertEmpty(
            $params,
            'FROM with subquery should have no params.',
        );

        // query subquery with params
        $subquery = new Query();

        $subquery->from('user')->where('account_id = :id', ['id' => 1]);

        $query = new Query();

        $query->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'FROM with subquery and params does not match.',
        );
        self::assertEquals(
            [
                'id' => 1,
                'abc' => 'abc',
            ],
            $params, 'FROM with subquery params do not match.',
        );

        // simple subquery
        $subquery = <<<SQL
        (SELECT * FROM user WHERE account_id = accounts.id)
        SQL;

        $query = new Query();

        $query->from(['activeusers' => $subquery]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'FROM with simple subquery string does not match.',
        );
        self::assertEmpty(
            $params,
            'FROM with simple subquery string should have no params.',
        );
    }

    public function testOrderBy(): void
    {
        // simple string
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->orderBy('name ASC, date DESC');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'ORDER BY with simple string does not match.',
        );
        self::assertEmpty(
            $params,
            'ORDER BY with simple string should have no params.',
        );

        // array syntax
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'ORDER BY with array syntax does not match.',
        );
        self::assertEmpty(
            $params,
            'ORDER BY with array syntax should have no params.',
        );

        // expression
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC
            SQL,
        );

        self::assertSame(
            $expected,
            $sql, 'ORDER BY with Expression does not match.',
        );
        self::assertEmpty(
            $params,
            'ORDER BY with Expression should have no params.',
        );

        // expression with params
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'ORDER BY with Expression and params does not match.',
        );
        self::assertSame(
            [':to' => 4],
            $params,
            'ORDER BY Expression params do not match.',
        );
    }

    public function testGroupBy(): void
    {
        // simple string
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->groupBy('name, date');

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'GROUP BY with simple string does not match.',
        );
        self::assertEmpty(
            $params,
            'GROUP BY with simple string should have no params.',
        );

        // array syntax
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->groupBy(['name', 'date']);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'GROUP BY with array syntax does not match.',
        );
        self::assertEmpty(
            $params,
            'GROUP BY with array syntax should have no params.',
        );

        // expression
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'GROUP BY with Expression does not match.',
        );
        self::assertEmpty(
            $params,
            'GROUP BY with Expression should have no params.',
        );

        // expression with params
        $query = new Query();

        $query
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        $expected = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x
            SQL,
        );

        self::assertSame(
            $expected,
            $sql,
            'GROUP BY with Expression and params does not match.',
        );
        self::assertSame(
            [':to' => 4],
            $params,
            'GROUP BY Expression params do not match.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertProvider')]
    public function testInsert($table, $columns, $params, $expectedSQL, $expectedParams, $replaceQuotes = true): void
    {
        $actualParams = $params;

        $actualSQL = $this->getDb()->queryBuilder->insert(
            $table,
            $columns,
            $actualParams,
        );

        if ($replaceQuotes) {
            $expectedSQL = $this->replaceQuotes($expectedSQL);
        }

        self::assertSame(
            $expectedSQL,
            $actualSQL,
            'INSERT SQL does not match expected output.',
        );
        self::assertSame(
            $expectedParams,
            $actualParams,
            'INSERT params do not match expected params.',
        );
    }

    /**
     * Dummy test to speed up QB's tests which rely on DB schema
     */
    public function testInitFixtures(): void
    {
        self::assertInstanceOf(
            QueryBuilder::class,
            $this->getDb()->queryBuilder,
            'DB connection does not have a QueryBuilder instance.',
        );
    }

    #[Depends('testInitFixtures')]
    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertProvider')]
    public function testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getConnection(true, $this->driverName === 'sqlite')->getQueryBuilder()->upsert(
            $table,
            $insertColumns,
            $updateColumns,
            $actualParams,
        );

        if (is_string($expectedSQL)) {
            self::assertSame(
                $expectedSQL,
                $actualSQL,
                'UPSERT SQL does not match expected output.',
            );
        } else {
            self::assertContains(
                $actualSQL,
                $expectedSQL,
                'UPSERT SQL is not in the list of expected outputs.',
            );
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            self::assertSame(
                $expectedParams,
                $actualParams,
                'UPSERT params do not match expected params.',
            );
        } else {
            $this->assertIsOneOf(
                $actualParams,
                $expectedParams,
            );
        }
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsertProvider')]
    public function testBatchInsert($table, $columns, $value, $expected, $replaceQuotes = true): void
    {
        $sql = $this->getDb()->queryBuilder->batchInsert(
            $table,
            $columns,
            $value,
        );

        if ($replaceQuotes) {
            $expected = $this->replaceQuotes($expected);
        }

        self::assertSame(
            $expected,
            $sql,
            'Batch INSERT SQL does not match expected output.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'updateProvider')]
    public function testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getDb()->queryBuilder->update(
            $table,
            $columns,
            $condition,
            $actualParams,
        );

        self::assertSame(
            $this->replaceQuotes($expectedSQL),
            $actualSQL,
            'UPDATE SQL does not match expected output.',
        );
        self::assertSame(
            $expectedParams,
            $actualParams,
            'UPDATE params do not match expected params.',
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'deleteProvider')]
    public function testDelete($table, $condition, $expectedSQL, $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getDb()->queryBuilder->delete(
            $table,
            $condition,
            $actualParams,
        );

        self::assertSame(
            $this->replaceQuotes($expectedSQL),
            $actualSQL,
            'DELETE SQL does not match expected output.',
        );
        self::assertSame(
            $expectedParams,
            $actualParams,
            'DELETE params do not match expected params.',
        );
    }

    public function testCommentColumn(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        ALTER TABLE [[comment]] CHANGE [[add_comment]] [[add_comment]] varchar(255) NOT NULL COMMENT 'This is my column.'
        SQL;

        $sql = $qb->addCommentOnColumn(
            'comment',
            'add_comment',
            'This is my column.',
        );

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Add column comment SQL does not match.',
        );

        $expected = <<<SQL
        ALTER TABLE [[comment]] CHANGE [[replace_comment]] [[replace_comment]] varchar(255) DEFAULT NULL COMMENT 'This is my column.'
        SQL;

        $sql = $qb->addCommentOnColumn(
            'comment',
            'replace_comment',
            'This is my column.',
        );

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Replace column comment SQL does not match.',
        );

        $expected = <<<SQL
        ALTER TABLE [[comment]] CHANGE [[delete_comment]] [[delete_comment]] varchar(128) NOT NULL COMMENT ''
        SQL;

        $sql = $qb->dropCommentFromColumn(
            'comment',
            'delete_comment',
        );

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Drop column comment SQL does not match.',
        );
    }

    public function testCommentTable(): void
    {
        $qb = $this->getDb()->queryBuilder;

        $expected = <<<SQL
        ALTER TABLE [[comment]] COMMENT 'This is my table.'
        SQL;

        $sql = $qb->addCommentOnTable(
            'comment',
            'This is my table.',
        );

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Add table comment SQL does not match.',
        );

        $expected = <<<SQL
        ALTER TABLE [[comment]] COMMENT ''
        SQL;

        $sql = $qb->dropCommentFromTable(
            'comment',
        );

        self::assertSame(
            $this->replaceQuotes($expected),
            $sql,
            'Drop table comment SQL does not match.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15653
     */
    public function testIssue15653(): void
    {
        $query = (new Query())
            ->from('admin_user')
            ->where(['is_deleted' => false]);

        $query
            ->where([])
            ->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $this->getDb()->queryBuilder->build($query);

        self::assertSame(
            $this->replaceQuotes(
                <<<SQL
                SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)
                SQL,
            ),
            $sql,
            'Issue 15653: IN condition with overwritten where does not match.',
        );
        self::assertSame(
            [
                ':qp0' => '1',
                ':qp1' => '0',
            ],
            $params,
            'Issue 15653: IN condition params do not match.',
        );
    }
}
