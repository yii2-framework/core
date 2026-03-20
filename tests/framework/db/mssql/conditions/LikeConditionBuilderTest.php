<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql\conditions;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;
use yiiunit\framework\db\mssql\conditions\providers\LikeConditionBuilderProvider;

/**
 * Unit test for {@see \yii\db\mssql\conditions\LikeConditionBuilder} with MSSQL driver.
 *
 * {@see LikeConditionBuilderProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('condition')]
#[Group('mssql')]
final class LikeConditionBuilderTest extends BaseDatabase
{
    protected $driverName = 'sqlsrv';

    #[DataProviderExternal(LikeConditionBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(array|object $condition, string $expected, array $expectedParams): void
    {
        $query = (new Query())->where($condition);

        $db = $this->getConnection(false, false);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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
