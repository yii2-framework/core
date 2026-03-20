<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\base\db\conditions\providers\NotConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\conditions\NotConditionBuilder} with SQLite driver.
 *
 * {@see NotConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('sqlite')]
final class NotConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'sqlite';

    #[DataProviderExternal(NotConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $query = (new Query())->where($condition);

        $db = $this->getConnection(true, false);

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
}
