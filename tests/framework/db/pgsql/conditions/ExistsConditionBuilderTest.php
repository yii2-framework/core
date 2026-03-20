<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\base\db\conditions\providers\ExistsConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\conditions\ExistsConditionBuilder} with PostgreSQL driver.
 *
 * {@see ExistsConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('pgsql')]
final class ExistsConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'pgsql';

    #[DataProviderExternal(ExistsConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection(false, false);

        $query = new Query();

        $query->where($condition);

        [$sql, $params] = $db->queryBuilder->build($query);

        self::assertSame(
            'SELECT *' . ($expected === '' ? '' : ' WHERE ' . $this->replaceQuotes($expected)),
            $sql,
            'Generated SQL does not match expected SQL.',
        );
        self::assertSame(
            $expectedParams,
            $params,
            'Bound parameters do not match expected parameters.',
        );
    }

    #[DataProviderExternal(ExistsConditionBuilderProvider::class, 'existsWithFullQuery')]
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection(false, false);

        $subQuery = new Query();

        $subQuery->select('1')->from('Website w');

        $query = new Query();

        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            $this->replaceQuotes($expectedQuerySql),
            $actualQuerySql,
            'EXISTS query SQL does not match expected output.',
        );
        self::assertEmpty(
            $actualQueryParams,
            'EXISTS query should have no bound parameters.',
        );
    }

    #[DataProviderExternal(ExistsConditionBuilderProvider::class, 'existsWithParameters')]
    public function testBuildWhereExistsWithParameters(string $expectedQuerySql, array $expectedQueryParams): void
    {
        $db = $this->getConnection(false, false);

        $subQuery = new Query();

        $subQuery
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);

        $query = new Query();

        $query
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$actualQuerySql, $queryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            $this->replaceQuotes($expectedQuerySql),
            $actualQuerySql,
            'EXISTS with parameters SQL does not match.',
        );
        self::assertSame(
            $expectedQueryParams,
            $queryParams,
            'EXISTS with parameters params do not match.',
        );
    }

    #[DataProviderExternal(ExistsConditionBuilderProvider::class, 'existsWithArrayParameters')]
    public function testBuildWhereExistsWithArrayParameters(string $expectedQuerySql, array $expectedQueryParams): void
    {
        $db = $this->getConnection(false, false);

        $subQuery = new Query();

        $subQuery
            ->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => '210']);

        $query = new Query();

        $query
            ->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$actualQuerySql, $queryParams] = $db->queryBuilder->build($query);

        self::assertSame(
            $this->replaceQuotes($expectedQuerySql),
            $actualQuerySql,
            'EXISTS with array parameters SQL does not match.',
        );
        self::assertSame(
            $expectedQueryParams,
            $queryParams,
            'EXISTS with array parameters params do not match.',
        );
    }
}
